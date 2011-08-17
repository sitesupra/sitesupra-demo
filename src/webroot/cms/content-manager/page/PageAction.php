<?php

namespace Supra\Cms\ContentManager\Page;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Exception\DuplicatePagePathException;

/**
 * 
 */
class PageAction extends PageManagerAction
{
	/**
	 * @return string
	 */
	public function pageAction()
	{
		$controller = $this->getPageController();

		//FIXME: hardcoded now
		$locale = 'en';
		$media = Entity\Layout::MEDIA_SCREEN;
		$pageId = $_GET['page_id'];
		
		// Create special request
		$request = new PageRequestEdit($locale, $media);

		$response = $controller->createResponse($request);
		$controller->prepare($request, $response);

		// Entity manager 
		$em = $request->getDoctrineEntityManager();
		$pageDao = $em->getRepository(PageRequestEdit::PAGE_ABSTRACT_ENTITY);

		/* @var $page Entity\Abstraction\Page */
		$page = $pageDao->findOneById($pageId);
		
		if (empty($page)) {
			$this->getResponse()
					->setErrorMessage("Page does not exist");
			
			return;
		}
		
		$this->setInitialPageId($pageId);
		
		/* @var $pageData Entity\Abstraction\Data */
		$pageData = $page->getData($locale);
		
		if (empty($pageData)) {
			$this->getResponse()
					->setErrorMessage("Page does not exist");
			
			return;
		}
		
		$request->setRequestPageData($pageData);
		$controller->execute($request);

		$pathPart = null;
		$pathPrefix = null;
		
		//TODO: create some path for templates also
		if ($pageData instanceof Entity\PageData) {
			$pathPart = $pageData->getPathPart();
			
			if ($page->hasParent()) {
				$pathPrefix = $page->getParent()
						->getPath();
			}
		}
		
		$array = array(
			'id' => $page->getId(),
			'title' => $pageData->getTitle(),
			'path' => $pathPart,
			'path_prefix' => $pathPrefix,
			'keywords' => 'web development, web design, nearshore development, e-commerce, visualization, 3D, web 2.0, PHP, LAMP, SiteSupra Platform, CMS, content management, web application, Web systems, IT solutions, usability improvements, system design, FMS, SFS, design conception, design solutions, intranet systems development, extranet systems development, flash development, hitask',
			'description' => '',
			'template' =>
			array(
				'id' => 'template_3',
				'title' => 'Simple',
				'img' => '/cms/supra/img/templates/template-3.png',
			),
			'scheduled_date' => '18.08.2011',
			'scheduled_time' => '08:00',
			'version' =>
			array(
				'id' => 222,
				'title' => 'Draft (auto-saved)',
				'author' => 'Admin',
				'date' => '21.05.2011',
			),
			'active' => true,
			'internal_html' => $response->getOutput(),
			'contents' => array()
		);
		
		$contents = array();
		$page = $request->getPage();
		$placeHolderSet = $request->getPlaceHolderSet()
				->getFinalPlaceHolders();
		$blockSet = $request->getBlockSet();
		$blockPropertySet = $request->getBlockPropertySet();
		
		/* @var $placeHolder Entity\Abstraction\PlaceHolder */
		foreach ($placeHolderSet as $placeHolder) {
			
			$placeHolderData = array(
				'id' => $placeHolder->getName(),
				'type' => 'list',
				'locked' => ! $page->isPlaceHolderEditable($placeHolder),

				//TODO: not specified now
				'allow' => array(
					0 => 'Project_Text_TextController',
				),
				'contents' => array()
			);

			$blockSubset = $blockSet->getPlaceHolderBlockSet($placeHolder);


			/* @var $block Entity\Abstraction\Block */
			foreach ($blockSubset as $block) {

				$blockData = array(
					'id' => $block->getId(),
					//TODO: move normalizing to somewhere else
					'type' => trim(str_replace('\\', '_', $block->getComponent()), '_'),
					'locked' => ! $page->isBlockEditable($block),
					'properties' => array(),
				);

				$blockPropertySubset = $blockPropertySet->getBlockPropertySet($block);

				/* @var $blockProperty Entity\BlockProperty */
				foreach ($blockPropertySubset as $blockProperty) {
					if ($page->isBlockPropertyEditable($blockProperty)) {
						$propertyData = array(
							$blockProperty->getName() => array(
								'html' => $blockProperty->getValue(),
								'data' => array()
							),
						);

						$blockData['properties'][] = $propertyData;
					}
				}

				$placeHolderData['contents'][] = $blockData;
			}
				
			$array['contents'][] = $placeHolderData;
		}
		
		$this->getResponse()->setResponseData($array);
	}
	
	/**
	 * Creates a new page
	 * @TODO: create action for templates as well
	 */
	public function createAction()
	{
		$this->isPostRequest();
		
		$parentId = $this->getRequestParameter('parent');
		$parent = null;
		$templateId = $this->getRequestParameter('template');
		$locale = $this->getLocale();
		
		$page = new Entity\Page();
		$pageData = new Entity\PageData($locale);
		$pageData->setMaster($page);
		
		$templateDao = $this->entityManager->getRepository(PageRequest::TEMPLATE_ENTITY);
		$template = $templateDao->findOneById($templateId);
		
		if (empty($template)) {
			$this->getResponse()->setErrorMessage("Template not specified or found");
			
			return;
		}
		
		$page->setTemplate($template);
		
		$pathPart = '';
		if ($this->hasRequestParameter('path')) {
			$pathPart = $this->getRequestParameter('path');
		}
		
		if ($this->hasRequestParameter('title')) {
			$title = $this->getRequestParameter('title');
			$pageData->setTitle($title);
		}
		
		// Find parent page
		if (isset($parentId)) {
			$pageDao = $this->entityManager->getRepository(PageRequest::PAGE_ENTITY);
			$parent = $pageDao->findOneById($parentId);
			
			if (empty($parent)) {
				$this->getResponse()->setErrorMessage("Parent page not specified or found");

				return;
			}
			
		}
		
		$this->entityManager->persist($page);
		$this->entityManager->persist($pageData);
		
		// Set parent
		if ( ! empty($parent)) {
			$page->moveAsLastChildOf($parent);
		}
		
		//TODO: try fixing maybe? Must be run to regenerate full path
		$pathValid = false;
		$i = 2;
		$suffix = '';
		
		do {
			try {
				$pageData->setPathPart($pathPart . $suffix);
				$pathValid = true;
			} catch (DuplicatePagePathException $pathInvalid) {
				$suffix = '-' . $i;
				$i++;
				
				// Loop stopper
				if ($i > 100) {
					throw $pathInvalid;
				}
			}
		} while ( ! $pathValid);
		
		$this->entityManager->flush();
		
		$this->outputPage($pageData);
	}
	
//	public function saveAction()
//	{
//		1+1;
//	}
	
	/**
	 * Called when page delete is requested
	 * @TODO: for now the page is not removed, only it's localization
	 */
	public function deleteAction()
	{
		$this->isPostRequest();
		
		$pageId = $this->getRequestParameter('page_id');
		$locale = $this->getLocale();
		
		$pageDao = $this->entityManager->getRepository(PageRequest::PAGE_ABSTRACT_ENTITY);
		/* @var $page Entity\Abstraction\Page */
		$page = $pageDao->findOneById($pageId);
		
		if (empty($page)) {
			$this->getResponse()
					->setErrorMessage("Page doesn't exist already");
			
			return;
		}
		
		// Check if there is no children
		$children = $page->getChildren();
		
		foreach ($children as $child) {
			/* @var $child Entity\Abstraction\Page */
			$childData = $child->getData($locale);
			
			if ( ! empty($childData)) {
				$this->getResponse()
					->setErrorMessage("Cannot remove page with children");
				
				return;
			}
		}
		
		$pageData = $page->getData($locale);
		
		if (empty($pageData)) {
			$this->getResponse()
					->setErrorMessage("Page doesn't exist in language '$locale'");
			
			return;
		}
		
		$this->entityManager->remove($pageData);
		$this->entityManager->flush();
	}
	
}
