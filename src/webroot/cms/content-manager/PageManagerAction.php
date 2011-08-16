<?php

namespace Supra\Cms\ContentManager;

use Supra\Controller\Pages\Entity;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Doctrine\ORM\EntityManager;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Log\Log;
use Supra\Controller\Pages\Repository\PageRepository;
use Supra\Http\Cookie;

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
	 * @var Log
	 */
	protected $log;
	
	/**
	 * Assign entity manager, log
	 */
	public function __construct()
	{
		// Take entity manager of the page controller
		$controller = $this->getPageController();
		$this->entityManager = ObjectRepository::getEntityManager($controller);
		
		$this->log = ObjectRepository::getLogger($this);
	}
	
	/**
	 * TODO: must return configurable controller instance (use repository?)
	 * @return PageController
	 */
	protected function getPageController()
	{
		$controller = new \Project\Pages\PageController();
		
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
	 * TODO: hardcoded now, maybe should reutrn locale object (!!!)
	 * @return string
	 */
	protected function getLocale()
	{
		return 'en';
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
		$locale = $this->getLocale();
		$pageDao = $this->entityManager->getRepository(PageRequest::PAGE_ABSTRACT_ENTITY);
		$page = null;
		
		// Try cookie
		if (isset($_COOKIE[self::INITIAL_PAGE_ID_COOKIE])) {
			$pageId = $_COOKIE[self::INITIAL_PAGE_ID_COOKIE];
			$page = $pageDao->findOneById($pageId);
			
			// Page localization must exist
			$pageData = $page->getData($locale);
			
			if (empty($pageData)) {
				$page = null;
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
}
