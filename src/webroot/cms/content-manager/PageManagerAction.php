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
		$locale = $this->getLocale();
		$media = $this->getMedia();

		$request = new PageRequestEdit($locale, $media);
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
		$locale = $this->getLocale();

		if (empty($pageId)) {
			throw new ResourceNotFoundException("Page ID not provided");
		}

		$pageDao = $this->entityManager->getRepository(PageRequest::PAGE_ABSTRACT_ENTITY);

		/* @var $page \Supra\Controller\Pages\Entity\Abstraction\Page */
		$page = $pageDao->findOneById($pageId);

		if (empty($page)) {
			throw new ResourceNotFoundException("Page by ID {$pageId} not found");
		}

		$pageData = $page->getData($locale);

		if (empty($pageData)) {
			throw new ResourceNotFoundException("Page data for page {$pageId} locale {$locale} not found");
		}

		return $pageData;
	}

	/**
	 * Get first page ID to show in the CMS
	 * @return int
	 */
	protected function getInitialPageId()
	{
		$locale = $this->getLocale()->getId();
		$pageDao = $this->entityManager->getRepository(PageRequest::PAGE_ABSTRACT_ENTITY);
		$page = null;

		// Try cookie
		if (isset($_COOKIE[self::INITIAL_PAGE_ID_COOKIE])) {
			$pageId = $_COOKIE[self::INITIAL_PAGE_ID_COOKIE];
			$page = $pageDao->findOneById($pageId);

			if ( ! empty($page)) {
				// Page localization must exist
				$pageData = $page->getData($locale);

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
		
		$copyContent = function() use ($pageData, $publicEm, $draftEm) {
		
			$pageId = $pageData->getMaster()->getId();
			$locale = $pageData->getLocale();
			$pageDataId = $pageData->getId();

			$draftPage = $pageData->getMaster();

			$draftEm->detach($pageData);
			$pageData = $publicEm->merge($pageData);

			/* @var $publicPage Entity\Abstraction\Page */
			$publicPage = $publicEm->find(PageRequest::PAGE_ABSTRACT_ENTITY, $pageId);
			$pageData->setMaster($publicPage);
			$blockPropertyEntity = PageRequest::BLOCK_PROPERTY_ENTITY;

			// Delete all block properties by 'data' and 'block'
			{
				$dql = "SELECT p FROM $blockPropertyEntity p
						JOIN p.block b
						JOIN b.placeHolder ph
						WHERE b.locale = ?0 AND (ph.master = ?1 OR p.data = ?2)";

				$query = $publicEm->createQuery($dql);
				$query->execute(array($locale, $pageId, $pageDataId));
				$properties = $query->getResult();

				foreach ($properties as $property) {
					$publicEm->remove($property);
				}
			}

			// Delete all blocks
			{
				$blockEntity = PageRequest::BLOCK_ENTITY;

				$dql = "SELECT b FROM $blockEntity b
						JOIN b.placeHolder ph
						WHERE b.locale = ?0 AND ph.master = ?1";

				$query = $publicEm->createQuery($dql);
				$query->execute(array($locale, $pageId));
				$blocks = $query->getResult();

				foreach ($blocks as $block) {
					$publicEm->remove($block);
				}
			}

			$publicEm->flush();

			/* @var $pageData Entity\Abstraction\Data */
			$draftPlaceHolders = $draftPage->getPlaceHolders();
			/* @var $placeHolder Entity\Abstraction\PlaceHolder */
			foreach ($draftPlaceHolders as $placeHolder) {
				$draftEm->detach($placeHolder);
				$publicEm->merge($placeHolder);

				$blocks = $placeHolder->getBlocks();

				/* @var $block Entity\Abstraction\Block */
				foreach ($blocks as $block) {
					$draftEm->detach($block);
					$publicEm->merge($block);
				}
			}

			{
				$dql = "SELECT p FROM $blockPropertyEntity p
						JOIN p.block b
						JOIN b.placeHolder ph
						WHERE b.locale = ?0 AND (ph.master = ?1 OR p.data = ?2)";

				$query = $draftEm->createQuery($dql);
				$query->execute(array($locale, $pageId, $pageDataId));
				$properties = $query->getResult();

				foreach ($properties as $property) {
					$publicEm->merge($property);
				}
			}

			$publicEm->flush();
		};
		
		$publicEm->transactional($copyContent);
	}

}
