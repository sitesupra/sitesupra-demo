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

/**
 * Controller containing common methods
 */
abstract class PageManagerAction extends CmsAction
{
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
}
