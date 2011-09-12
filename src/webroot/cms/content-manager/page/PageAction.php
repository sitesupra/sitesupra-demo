<?php

namespace Supra\Cms\ContentManager\Page;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Exception\DuplicatePagePathException;
use Supra\ObjectRepository\ObjectRepository;
use Supra\FileStorage\Entity\Image;
use Supra\FileStorage\Entity\File;

/**
 * 
 */
class PageAction extends PageManagerAction
{
	/**
	 * Returns all page information required for load
	 */
	public function pageAction()
	{
		$controller = $this->getPageController();
		$locale = $this->getLocale();
		$media = $this->getMedia();
		$pageId = $this->getRequestParameter('page_id');

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
		$templateArray = array();
		$scheduledDateTime = null;
		$scheduledDate = null;
		$scheduledTime = null;
		$metaKeywords = null;
		$metaDescription = null;
		$active = true;
		
		//TODO: create some path for templates also (?)
		if ($page instanceof Entity\Page) {
			
			/* @var $pageData Entity\PageData */
			$pathPart = $pageData->getPathPart();
			
			if ($page->hasParent()) {
				$pathPrefix = $page->getParent()
						->getPath();
			}
			
			$template = $pageData->getTemplate();
			$templateData = $template->getData($locale);
			
			if ( ! $templateData instanceof Entity\TemplateData) {
				throw new Exception\RuntimeException("Template doesn't exist for page $page in locale $locale");
			}
			
			$templateArray = array(
				'id' => $template->getId(),
				'title' => $templateData->getTitle(),
				//TODO: hardcoded
				'img' => '/cms/lib/supra/img/templates/template-1.png',
			);
			
			$scheduledDateTime = $pageData->getScheduleTime();
			$metaKeywords = $pageData->getMetaKeywords();
			$metaDescription = $pageData->getMetaDescription();
			$active = $pageData->isActive();
		}
		
		if ( ! is_null($scheduledDateTime)) {
			$scheduledDate = $scheduledDateTime->format('Y-m-d');
			$scheduledTime = $scheduledDateTime->format('H:i');
		}
		
		$type = 'page';
		
		if($page instanceof Entity\Template) {
			$type = 'template';

			$layout = null;
			if ($page->isRoot()){
				$layout = $page->getLayout(Entity\Layout::MEDIA_SCREEN)
						->getFile();
			}
		}
		
		$array = array(
			'id' => $page->getId(),
			'title' => $pageData->getTitle(),
			'path' => $pathPart,
			'path_prefix' => $pathPrefix,
			'template' => $templateArray,
			'type' => $type,
			'internal_html' => $response->__toString(),
			'contents' => array(),
			'layout' => $layout,
			'keywords' => $metaKeywords,
			'description' => $metaDescription,
			'scheduled_date' => $scheduledDate,
			'scheduled_time' => $scheduledTime,
			
			//TODO: check parents?
			'active' => $active
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
					'type' => $block->getComponentName(),
					'locked' => ! $page->isBlockEditable($block),
					'properties' => array(),
				);

				$blockPropertySubset = $blockPropertySet->getBlockPropertySet($block);

				/* @var $blockProperty Entity\BlockProperty */
				foreach ($blockPropertySubset as $blockProperty) {
					if ($page->isBlockPropertyEditable($blockProperty)) {
						
						$propertyName = $blockProperty->getName();
						$propertyValue = $blockProperty->getValue();
						$propertyValueData = $blockProperty->getValueData();
						
						$propertyValueData = $this->fillPropertyData($propertyValueData);
						
						$propertyData = array(
							'html' => $propertyValue,
							'data' => $propertyValueData
						);

						$blockData['properties'][$propertyName] = $propertyData;
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
		
		$pageData->setTemplate($template);
		
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
		
		$pathValid = false;
		$i = 2;
		$suffix = '';
		
		do {
			try {
				$pageData->setPathPart($pathPart . $suffix);
				$this->entityManager->flush();
				
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
		
		$this->outputPage($pageData);
	}
	
	/**
	 * Page save request, does nothing now
	 */
	public function saveAction()
	{
		$this->isPostRequest();
	}
	
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
	
	/**
	 * Called on page publish
	 */
	public function publishAction()
	{
		$this->isPostRequest();
		
		$pageData = $this->getPageData();
	}
	
	/**
	 * FIXME: it's not the best place to imlement this additional image/link data loading
	 * @param array $data
	 * @return array
	 */
	protected function fillPropertyData(array $data = null)
	{
		if (empty($data)) {
			return $data;
		}

		foreach ($data as &$dataItem) {
			switch ($dataItem['type']) {
				
				// Read the additional file info from the file storage
				case 'image':
					$locale = $this->getLocale();
					$imageId = $dataItem['image'];
					$fs = ObjectRepository::getFileStorage($this);
					$em = $fs->getDoctrineEntityManager();
					$image = $em->find('Supra\FileStorage\Entity\Image', $imageId);
					
					if ($image instanceof Image) {
						$info = $fs->getFileInfo($image, $locale);
						$dataItem['image'] = $info;
					}
					
					break;
				
				// Need to get file path for links to the file
				case 'link':
					
					if ($dataItem['resource'] == 'file') {
						
						$locale = $this->getLocale();
						$fileId = $dataItem['file_id'];
						$fs = ObjectRepository::getFileStorage($this);
						$em = $fs->getDoctrineEntityManager();
						$file = $em->find('Supra\FileStorage\Entity\File', $fileId);
						
						if ($file instanceof File) {
							$fileInfo = $fs->getFileInfo($file, $locale);
							$dataItem['file_path'] = $fileInfo['path'];
						}
					}
					
					break;
			}
		}
		
		return $data;
	}
	
}
