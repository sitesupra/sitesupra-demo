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
	 * @var Entity\Abstraction\Data
	 */
	protected $pageData;

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
		if (isset($this->pageData)) {
			return $this->pageData;
		}
		
		$pageId = $this->getRequestParameter('page_id');
		$localeId = $this->getLocale()->getId();

		if (empty($pageId)) {
			throw new ResourceNotFoundException("Page ID not provided");
		}
		
		$dataEntity = PageRequest::DATA_ENTITY;
		$dql = "SELECT d FROM $dataEntity d WHERE d.master = ?0 AND d.locale = ?1";
		$query = $this->entityManager->createQuery($dql);
		$query->execute(array($pageId, $localeId));

		try {
			$this->pageData = $query->getSingleResult();
			
			return $this->pageData;
		} catch (\Doctrine\ORM\NoResultException $notFound) {
			throw new ResourceNotFoundException("Page data for page {$pageId} locale {$localeId} not found", null, $notFound);
		}
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
	 * Will publish page currently inside pageData property or found by page_id
	 * and locale query parameters
	 */
	protected function publish()
	{
		$controller = $this->getPageController();
		$publicEm = ObjectRepository::getEntityManager($controller);
		
		$pageRequest = $this->getPageRequest();
		
		$copyContent = function() use ($pageRequest, $publicEm) {
			$pageRequest->publish($publicEm);
		};
		
		$publicEm->transactional($copyContent);
	}
	
}
