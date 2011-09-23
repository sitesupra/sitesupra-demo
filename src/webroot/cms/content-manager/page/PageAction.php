<?php

namespace Supra\Cms\ContentManager\Page;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Exception\DuplicatePagePathException;
use Supra\User\Entity\Abstraction\User;
use Project\Authentication\AuthenticateSessionNamespace;

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
		$localeId = $this->getLocale()->getId();
		$media = $this->getMedia();
		$pageId = $this->getRequestParameter('page_id');

		// Create special request
		$request = new PageRequestEdit($localeId, $media);

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
		$pageData = $page->getData($localeId);

		if (empty($pageData)) {
			$this->getResponse()
					->setErrorMessage("Page does not exist");

			return;
		}

		$request->setPageData($pageData);
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
		$redirect = null;

		//TODO: create some path for templates also (?)
		if ($page instanceof Entity\Page) {

			/* @var $pageData Entity\PageData */
			$pathPart = $pageData->getPathPart();

			if ($page->hasParent()) {
				$pathPrefix = $page->getParent()
						->getPath();
			}

			$template = $pageData->getTemplate();
			$templateData = $template->getData($localeId);

			if ( ! $templateData instanceof Entity\TemplateData) {
				throw new Exception\RuntimeException("Template doesn't exist for page $page in locale $localeId");
			}

			$templateArray = array(
				'id' => $template->getId(),
				'title' => $templateData->getTitle(),
				//TODO: hardcoded
				'img' => '/cms/lib/supra/img/templates/template-1.png',
			);

			$scheduledDateTime = $pageData->getScheduleTime();
			$redirectLink = $pageData->getRedirect();
			$metaKeywords = $pageData->getMetaKeywords();
			$metaDescription = $pageData->getMetaDescription();
			$active = $pageData->isActive();
			
			if ( ! is_null($redirectLink)) {
				$redirect = $this->convertReferencedElementToArray($redirectLink);
			}
		}

		if ( ! is_null($scheduledDateTime)) {
			$scheduledDate = $scheduledDateTime->format('Y-m-d');
			$scheduledTime = $scheduledDateTime->format('H:i');
		}
		
		$type = 'page';

		if ($page instanceof Entity\Template) {
			$type = 'template';
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
			'keywords' => $metaKeywords,
			'description' => $metaDescription,
			'scheduled_date' => $scheduledDate,
			'scheduled_time' => $scheduledTime,
			'redirect' => $redirect,
			'active' => $active
		);
		
		if ($page instanceof Entity\Template) {
			$layout = null;
			$root = false;
			if ($page->isRoot()) {
				$layout = $page->getLayout(Entity\Layout::MEDIA_SCREEN)
						->getFile();
				$root = true;
			}
			$array['layout'] = $layout;
			$array['root'] = $root;
		}

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
						$metadataCollection = $blockProperty->getMetadata();
						$data = array();

						/* @var $metadata Entity\BlockPropertyMetadata */
						foreach ($metadataCollection as $name => $metadata) {
							$referencedElement = $metadata->getReferencedElement();
							$data[$name] = $this->convertReferencedElementToArray($referencedElement);
						}
						
						$propertyData = array(
							'html' => $propertyValue,
							'data' => $data
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
		$localeId = $this->getLocale()->getId();

		$page = new Entity\Page();
		$pageData = new Entity\PageData($localeId);
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
				$i ++;

				// Loop stopper
				if ($i > 100) {
					throw $pathInvalid;
				}
			}
		} while ( ! $pathValid);

		$this->outputPage($pageData);
	}

	/**
	 * Called when save is performed inside the sitemap
	 */
	public function saveAction()
	{
		$this->isPostRequest();
		$pageData = $this->getPageData();

		if ($this->hasRequestParameter('title')) {
			$title = $this->getRequestParameter('title');
			$pageData->setTitle($title);
		}

		if ($this->hasRequestParameter('path')) {
			if ($pageData instanceof Entity\PageData) {
				$pathPart = $this->getRequestParameter('path');

				try {
					$pageData->setPathPart($pathPart);
				} catch (DuplicatePagePathException $uniqueException) {
					$this->getResponse()
							->setErrorMessage("{#sitemap.error.duplicate_path#}");

					// Clear the unit of work
					$this->entityManager->clear();
				}
			}
		}
		
		if ($this->hasRequestParameter('template')) {
			$templateId = $this->getRequestParameter('template');

			/* @var $template Entity\Template */
			$template = $this->entityManager->find(PageRequest::TEMPLATE_ENTITY, $templateId);
			$pageData->setTemplate($template);
		}
		
		$this->entityManager->flush();
		$this->outputPage($pageData);
	}

	/**
	 * Called when page delete is requested
	 * @TODO: for now the page is not removed, only it's localization
	 */
	public function deleteAction()
	{
		$this->isPostRequest();

		$pageId = $this->getRequestParameter('page_id');
		$localeId = $this->getLocale()->getId();

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
			$childData = $child->getData($localeId);

			if ( ! empty($childData)) {
				$this->getResponse()
						->setErrorMessage("Cannot remove page with children");

				return;
			}
		}

		$pageData = $page->getData($localeId);

		if (empty($pageData)) {
			$this->getResponse()
					->setErrorMessage("Page doesn't exist in language '$localeId'");

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
		// Must be executed with POST method
		$this->isPostRequest();
		
		// This failed..
//		$this->checkActionPermission($this->getPageData(), Entity\Abstraction\Data::ACTION_PUBLISH_PAGE_NAME);
		
		$this->publish();
	}

}
