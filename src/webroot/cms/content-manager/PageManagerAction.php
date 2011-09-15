<?php

namespace Supra\Cms\ContentManager;

use Supra\Controller\Pages\Entity;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Doctrine\ORM\EntityManager;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Repository\PageRepository;
use Supra\Http\Cookie;
use Supra\Cms\CmsAction;
use Supra\NestedSet\Node\DoctrineNode;
use Doctrine\ORM\Query;
use Supra\Database\Doctrine\Hydrator\ColumnHydrator;

/**
 * Controller containing common methods
 */
abstract class PageManagerAction extends CmsAction
{

	const INITIAL_PAGE_ID_COOKIE = 'cms_content_manager_initial_page_id';

	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * Assign entity manager
	 */
	public function __construct()
	{
		parent::__construct();

		// Take entity manager of the page controller
//		$controller = $this->getPageController();
		// Will fetch connection for drafts
		$this->entityManager = ObjectRepository::getEntityManager($this);
	}

	/**
	 * TODO: must return configurable controller instance (use repository?)
	 * @return PageController
	 */
	protected function getPageController()
	{
		$controller = new \Project\Pages\PageController();

		// Override with the draft version connection
		$controller->setEntityManager($this->entityManager);
		
		return $controller;
	}

	/**
	 * @return PageRequestEdit
	 */
	protected function getPageRequest()
	{
		$controller = $this->getPageController();
		$localeId = $this->getLocale()->getId();
		$media = $this->getMedia();

		$request = new PageRequestEdit($localeId, $media);
		$response = $controller->createResponse($request);

		$controller->prepare($request, $response);

		$requestPageData = $this->getPageData();
		$request->setRequestPageData($requestPageData);

		return $request;
	}

	/**
	 * TODO: hardcoded now
	 * @return string
	 */
	protected function getMedia()
	{
		return 'screen';
	}

	/**
	 * @return Entity\Abstraction\Data
	 * @throws ResourceNotFoundException
	 */
	protected function getPageData()
	{
		$pageId = $this->getRequestParameter('page_id');
		$localeId = $this->getLocale()->getId();

		if (empty($pageId)) {
			throw new ResourceNotFoundException("Page ID not provided");
		}

		$pageDao = $this->entityManager->getRepository(PageRequest::PAGE_ABSTRACT_ENTITY);

		/* @var $page \Supra\Controller\Pages\Entity\Abstraction\Page */
		$page = $pageDao->findOneById($pageId);

		if (empty($page)) {
			throw new ResourceNotFoundException("Page by ID {$pageId} not found");
		}

		$pageData = $page->getData($localeId);

		if (empty($pageData)) {
			throw new ResourceNotFoundException("Page data for page {$pageId} locale {$localeId} not found");
		}

		return $pageData;
	}

	/**
	 * Get first page ID to show in the CMS
	 * @return int
	 */
	protected function getInitialPageId()
	{
		$localeId = $this->getLocale()->getId();
		$pageDao = $this->entityManager->getRepository(PageRequest::PAGE_ABSTRACT_ENTITY);
		$page = null;

		// Try cookie
		if (isset($_COOKIE[self::INITIAL_PAGE_ID_COOKIE])) {
			$pageId = $_COOKIE[self::INITIAL_PAGE_ID_COOKIE];
			$page = $pageDao->findOneById($pageId);

			if ( ! empty($page)) {
				// Page localization must exist
				$pageData = $page->getData($localeId);

				if (empty($pageData)) {
					$page = null;
				}
			}
		}

		// Root page otherwise
		if (empty($page)) {
			$pageDao = $this->entityManager->getRepository(PageRequest::PAGE_ENTITY);
			/* @var $pageDao PageRepository */
			$pages = $pageDao->getRootNodes();
			if (isset($pages[0])) {
				$page = $pages[0];
			}
		}

		if (empty($page)) {
			return null;
		}

		$pageId = $page->getId();

		return $pageId;
	}

	/**
	 * Sets initial page ID to show in the CMS
	 * @param int $pageId
	 */
	protected function setInitialPageId($pageId)
	{
		$cookie = new Cookie(self::INITIAL_PAGE_ID_COOKIE, $pageId);
		$cookie->setExpire('+1 month');

		$this->getResponse()->setCookie($cookie);
	}

	/**
	 * 
	 * @param Entity\Abstraction\Data $pageData
	 */
	protected function outputPage(Entity\Abstraction\Data $pageData)
	{
		$data = null;

		if ($pageData instanceof Entity\TemplateData) {
			$data = $this->prepareTemplateData($pageData);
		}

		if ($pageData instanceof Entity\PageData) {
			$data = $this->preparePageData($pageData);
		}

		$this->getResponse()->setResponseData($data);
	}

	private function prepareTemplateData(Entity\TemplateData $templateData)
	{

		$template = $templateData->getTemplate();
		$parent = $template->getParent();
		$parentId = null;

		if ( ! is_null($parent)) {
			$parentId = $parent->getId();
		}

		$data = array(
			'id' => $template->getId(),
			'parent' => $parentId,
			//TODO: hardcoded
			'icon' => 'page',
			'preview' => '/cms/lib/supra/img/sitemap/preview/blank.jpg'
		);

		return $data;
	}

	private function preparePageData(Entity\PageData $pageData)
	{

		$page = $pageData->getPage();
		$template = $pageData->getTemplate();
		$parent = $page->getParent();
		$parentId = null;

		if ( ! is_null($parent)) {
			$parentId = $parent->getId();
		}

		$data = array(
			'id' => $page->getId(),
			'title' => $pageData->getTitle(),
			'template' => $template->getId(),
			'parent' => $parentId,
			'path' => $pageData->getPathPart(),
			//TODO: hardcoded
			'icon' => 'page',
			'preview' => '/cms/lib/supra/img/sitemap/preview/blank.jpg'
		);

		return $data;
	}
	
	/**
	 * Called on page/template publish
	 */
	protected function publish()
	{
		// Must be executed with POST method
		$this->isPostRequest();
		
		// Search for draft and public entity managers
		$draftEm = $this->entityManager;
		
		$controller = $this->getPageController();
		$publicEm = ObjectRepository::getEntityManager($controller);
		
		// Don't do anything if connections are identic
		if ($draftEm === $publicEm) {
			$this->log->debug("Publish doesn't do anything because CMS and public database connections are identical");
			return;
		}
		
		$pageData = $this->getPageData();
		$self = $this;
		
		$copyContent = function() use ($pageData, $publicEm, $draftEm, $self) {
		
			$pageId = $pageData->getMaster()->getId();
			$localeId = $pageData->getLocale();
			$pageDataId = $pageData->getId();

			$draftPage = $pageData->getMaster();

			$pageData = $publicEm->merge($pageData);
			
			/* @var $publicPage Entity\Abstraction\Page */
			$publicPage = $publicEm->find(PageRequest::PAGE_ABSTRACT_ENTITY, $pageId);
			$pageData->setMaster($publicPage);
			
			// 1. Get all blocks to be copied
			$draftBlocks = $self->getBlocksInPage($draftEm, $pageData);
			
			// 2. Get all blocks existing in public
			$existentBlocks = $self->getBlocksInPage($publicEm, $pageData);
			
			// 3. Remove blocks in 2, not in 1, remove all referencing block properties first
			$draftBlockIdList = Entity\Abstraction\Entity::collectIds($draftBlocks);
			$existentBlockIdList = Entity\Abstraction\Entity::collectIds($existentBlocks);
			$removedBlockIdList = array_diff($existentBlockIdList, $draftBlockIdList);
			
			if ( ! empty($removedBlockIdList)) {
				$self->removeBlocks($publicEm, $removedBlockIdList);
			}
			
			// 4.1. Merge all placeholders, don't delete missing, let's keep them
			foreach ($draftBlocks as $block) {
				$placeholder = $block->getPlaceHolder();
				$publicEm->merge($placeholder);
			}
			
			// 4.2. Merge all blocks in 1
			foreach ($draftBlocks as $block) {
				$publicEm->merge($block);
			}
			
			// 5. Get properties to be copied (of a. self and b. template)
			$draftProperties = $self->getBlockPropertiesInPage($draftEm, $pageData);
			
			// 6. Property merge moved down to 10.
			
			// 7. For properties 5b get block, placeholder IDs, check their existance in public, get not existant
			/* @var $property Entity\BlockProperty */
			$missingBlockIdList = array();
			$blockIdList = array();
			
			foreach ($draftProperties as $property) {
				$blockId = $property->getBlock()->getId();
				
				// The problematic case when block is part of parent templates
				if ( ! in_array($blockId, $draftBlockIdList)) {
					$blockIdList[$blockId] = $blockId;
				}
			}
			
			if ( ! empty($blockIdList)) {
				$blockEntity = PageRequest::BLOCK_ENTITY;

				$qb = $publicEm->createQueryBuilder();
				$qb->from($blockEntity, 'b')
						->select('b.id')
						->where($qb->expr()->in('b', $blockIdList));

				$query = $qb->getQuery();
				$existentBlockIdList = $query->getResult(ColumnHydrator::HYDRATOR_ID);
				
				$missingBlockIdList = array_diff($blockIdList, $existentBlockIdList);
			}
			
			// 8. Merge missing place holders from 7 (reset $locked property)
			$draftPlaceHolderIdList = $self->getPlaceHolderIdList($draftEm, $missingBlockIdList);
			$publicPlaceHolderIdList = $self->loadEntitiesByIdList($publicEm, PageRequest::PLACE_HOLDER_ENTITY, $draftPlaceHolderIdList, 'e.id', ColumnHydrator::HYDRATOR_ID);
			$missingPlaceHolderIdList = array_diff($draftPlaceHolderIdList, $publicPlaceHolderIdList);
			
			$missingPlaceHolders = $self->loadEntitiesByIdList($draftEm, PageRequest::PLACE_HOLDER_ENTITY, $missingPlaceHolderIdList);
			
			/* @var $placeHolder Entity\Abstraction\PlaceHolder */
			foreach ($missingPlaceHolders as $placeHolder) {
				$placeHolder = $publicEm->merge($placeHolder);
				
				// Reset locked property
				if ($placeHolder instanceof Entity\TemplatePlaceHolder) {
					$placeHolder->setLocked(false);
				}
			}
			
			// 9. Merge missing blocks (add $temporary property)
			$missingBlocks = $self->loadEntitiesByIdList($draftEm, PageRequest::TEMPLATE_BLOCK_ENTITY, $missingBlockIdList);
			
			/* @var $block Entity\TemplateBlock */
			foreach ($missingBlocks as $block) {
				$block = $publicEm->merge($block);
				$block->setTemporary(true);
			}
			
			// 10. Merge all properties 5a (trying all properties)
			foreach ($draftProperties as $property) {
				$publicEm->merge($property);
			}

			$publicEm->flush();
		};
		
		$publicEm->transactional($copyContent);
	}
	
	/**
	 * @param EntityManager $em
	 * @param Entity\Abstraction\Data $data
	 * @return array 
	 */
	public function getBlocksInPage(EntityManager $em, Entity\Abstraction\Data $data)
	{
		$masterId = $data->getMaster()->getId();
		$locale = $data->getLocale();
		$blockEntity = PageRequest::BLOCK_ENTITY;
		
		$dql = "SELECT b FROM $blockEntity b 
				JOIN b.placeHolder p
				WHERE p.master = ?0 AND b.locale = ?1";
		
		$blocks = $em->createQuery($dql)
				->setParameters(array($masterId, $locale))
				->getResult();
		
		return $blocks;
	}
	
	/**
	 * Removes blocks with all properties by ID
	 * @param EntityManager $em
	 * @param array $blockIdList
	 */
	public function removeBlocks(EntityManager $em, array $blockIdList)
	{
		if (empty($blockIdList)) {
			return;
		}
		
		$blockPropetyEntity = PageRequest::BLOCK_PROPERTY_ENTITY;
		$blockEntity = PageRequest::BLOCK_ENTITY;
		
		$qb = $em->createQueryBuilder();
		$qb->delete($blockPropetyEntity, 'p')
				->where($qb->expr()->in('p.block', $blockIdList))
				->getQuery()
				->execute();
		
		$qb = $em->createQueryBuilder();
		$qb->delete($blockEntity, 'b')
				->where($qb->expr()->in('b', $blockIdList))
				->getQuery()
				->execute();
	}
	
	/**
	 * @param EntityManager $em
	 * @param Entity\Abstraction\Data $data
	 * @return array
	 */
	public function getBlockPropertiesInPage(EntityManager $em, Entity\Abstraction\Data $data)
	{
		$dataId = $data->getId();
		$masterId = $data->getMaster()->getId();
		$locale = $data->getLocale();
		$blockPropertyEntity = PageRequest::BLOCK_PROPERTY_ENTITY;
		
		$dql = "SELECT p FROM $blockPropertyEntity p
				JOIN p.block b
				JOIN b.placeHolder ph
				WHERE b.locale = ?0 AND (ph.master = ?1 OR p.data = ?2)";

		$properties = $em->createQuery($dql)
				->setParameters(array($locale, $masterId, $dataId))
				->getResult();
		
		return $properties;
	}
	
	/**
	 * Load place holder ID list from block ID list
	 * @param EntityManager $em
	 * @param array $blockIdList
	 * @return array
	 */
	public function getPlaceHolderIdList(EntityManager $em, array $blockIdList)
	{
		if (empty($blockIdList)) {
			return array();
		}
		
		$qb = $em->createQueryBuilder();
		$qb->from(PageRequest::BLOCK_ENTITY, 'b')
				->join('b.placeHolder', 'p')
				->select('DISTINCT p.id')
				->where($qb->expr()->in('b', $blockIdList));
		$query = $qb->getQuery();
		$placeHolderIdList = $query->getResult(ColumnHydrator::HYDRATOR_ID);
		
		return $placeHolderIdList;
	}
	
	/**
	 * Loads entities by ID list
	 * @param EntityManager $em
	 * @param string $entity
	 * @param array $idList 
	 * @return array
	 */
	public function loadEntitiesByIdList(EntityManager $em, $entity, array $idList, $select = 'e', $hydrationMode = Query::HYDRATE_OBJECT)
	{
		if (empty($idList)) {
			return array();
		}
		
		$qb = $em->createQueryBuilder();
		$qb->from($entity, 'e')
				->select($select)
				->where($qb->expr()->in('e', $idList));
		$query = $qb->getQuery();
		$list = $query->getResult($hydrationMode);
		
		return $list;
	}

}
