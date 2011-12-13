<?php

namespace Supra\Cms\ContentManager\Page;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Controller\Pages\Request\HistoryPageRequestView;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Exception\DuplicatePagePathException;
use Supra\Editable;
use Supra\Cms\Exception\CmsException;
use Supra\Authorization\Exception\EntityAccessDeniedException;
use Supra\Cms\Exception\ObjectLockedException;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Repository\PageRepository;
use Supra\Authorization\Exception\AuthorizationException;

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
		$pageData = $this->getPageLocalization();
		$pageId = $pageData->getId();

		// Create special request
		$request = $this->getPageRequest();

		$response = $controller->createResponse($request);
		$controller->prepare($request, $response);

		// Entity manager 
		$em = $request->getDoctrineEntityManager();
		$pageDao = $em->getRepository(PageRequestEdit::PAGE_ABSTRACT_ENTITY);

		$page = $pageData->getMaster();

		if (empty($page)) {
			$this->getResponse()
					->setErrorMessage("Page does not exist");

			return;
		}

		$this->setInitialPageId($pageId);

		/* @var $pageData Entity\Abstraction\Localization */
		$pageData = $page->getLocalization($localeId);

		if (empty($pageData) && $page->isGlobal()) {
			$existingPageData = $page->getLocalizations()->first();
			$pageData = $request->recursiveClone($existingPageData);
			$pageData->setTitle($existingPageData->getTitle());
			$pageData->setLocale($localeId);
			$pageData->setMaster($page);
		}

		if (empty($pageData)) {
			$this->getResponse()
					->setErrorMessage("Page does not exist");

			return;
		}

		$isAllowedEditing = true;
		try {
			$this->checkActionPermission($page, Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE);
		} catch (AuthorizationException $e) {
			try {
				$this->checkActionPermission($pageData, Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE);
			} catch (AuthorizationException $e) {
				$isAllowedEditing = false;
			}
		}
		
		$request->setPageLocalization($pageData);
		$controller->execute($request);

		$pathPart = null;
		$pathPrefix = null;
		$templateArray = array();
		$scheduledDate = null;
		$scheduledTime = null;
		$metaKeywords = null;
		$metaDescription = null;
		$active = true;
		$redirect = null;
		$createdDate = null;
		$createdTime = null;

		//TODO: create some path for templates also (?)
		if ($page instanceof Entity\Page) {

			/* @var $pageData Entity\PageLocalization */
			$pathPart = $pageData->getPathPart();

			if ($page->hasParent()) {
				$pathPrefix = $page->getParent()
						->getPath();
			}

			$template = $pageData->getTemplate();
			$templateData = $template->getLocalization($localeId);

			if ( ! $templateData instanceof Entity\TemplateLocalization) {
				throw new \Supra\Controller\Pages\Exception\RuntimeException("Template doesn't exist for page $page in locale $localeId");
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

			if ( ! is_null($scheduledDateTime)) {
				$scheduledDate = $scheduledDateTime->format('Y-m-d');
				$scheduledTime = $scheduledDateTime->format('H:i:s');
			}

			if ($pageData->isPublishTimeSet()) {
				$createdDateTime = $pageData->getCreationTime();
				$createdDate = $createdDateTime->format('Y-m-d');
				$createdTime = $createdDateTime->format('H:i:s');
			}
		}

		$type = 'page';

		if ($page instanceof Entity\Template) {
			$type = 'template';
		}

		$array = array(
				'id' => $pageData->getId(),
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
				'active' => $active,
				'created_date' => $createdDate,
				'created_time' => $createdTime,
				'global' => $page->getGlobal(),
				'allow_edit' => $isAllowedEditing,
		);
		
		if ($page instanceof Entity\Template) {
			$layout = null;
			
			if ($page->hasLayout($this->getMedia())) {
				$layout = $page->getLayout($this->getMedia())
						->getFile();
			}
			
			$array['layout'] = $layout;
		}
		
		$array['root'] = $page->isRoot();

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
					'locked' => ! $pageData->isPlaceHolderEditable($placeHolder),
					//TODO: not specified now
//					'allow' => array(
//							0 => 'Project_Text_TextController',
//					),
					'contents' => array()
			);

			$blockSubset = $blockSet->getPlaceHolderBlockSet($placeHolder);

			/* @var $block Entity\Abstraction\Block */
			foreach ($blockSubset as $block) {
				
				$controller = $block->createController();
				$block->prepareController($controller, $request);

				$blockData = array(
						'id' => $block->getId(),
						'type' => $block->getComponentName(),
						'locked' => ! $pageData->isBlockEditable($block),
						'properties' => array(),
				);

				$editables = $controller->getPropertyDefinition();
				
				foreach ($editables as $propertyName => $editable) {
					$blockProperty = $controller->getProperty($propertyName);
					
					if ($page->isBlockPropertyEditable($blockProperty)) {
						$propertyValue = $blockProperty->getValue();
						$metadataCollection = $blockProperty->getMetadata();
						$data = array();

						/* @var $metadata Entity\BlockPropertyMetadata */
						foreach ($metadataCollection as $name => $metadata) {
							$referencedElement = $metadata->getReferencedElement();
							$data[$name] = $this->convertReferencedElementToArray($referencedElement);
						}

						$propertyData = $propertyValue;

						if ($blockProperty->getEditable() instanceof Editable\Html) {
							$propertyData = array(
								'html' => $propertyValue,
								'data' => $data
							);
						}
						
						if ($blockProperty->getEditable() instanceof Editable\Link) {
							if (isset($data[0])) {
								$propertyData = $data[0];
							}
						}
						
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

		$type = $this->getRequestParameter('type');
		$templateId = $this->getRequestParameter('template');
		$parent = $this->getPageByRequestKey('parent');
		$localeId = $this->getLocale()->getId();

		$this->checkActionPermission($parent, Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE);

		$page = null;
//		$pageData = null;
		$pathPart = null;

		// Page types
		if ($type == Entity\Abstraction\Entity::GROUP_DISCR) {
			$page = new Entity\GroupPage();
//			$pageData = $page->getLocalization($localeId);
		}
		elseif ($type == Entity\Abstraction\Entity::APPLICATION_DISCR) {
			$page = new Entity\ApplicationPage();
//			$pageData = new Entity\ApplicationLocalization($localeId);
//			$pageData->setMaster($page);

			$applicationId = $this->getRequestParameter('application_id');
			$page->setApplicationId($applicationId);
		}
		else {
			$page = new Entity\Page();
//			$pageData = new Entity\PageLocalization($localeId);
//			$pageData->setMaster($page);
		}
		
		$pageData = Entity\Abstraction\Localization::factory($page, $localeId);

		$this->entityManager->persist($page);

		// Template ID
		if ($pageData instanceof Entity\PageLocalization) {
			$template = $this->entityManager->find(Entity\Template::CN(), $templateId);

			if (empty($template)) {
				$this->getResponse()->setErrorMessage("Template not specified or found");

				return;
			}

			$pageData->setTemplate($template);

			$pathPart = '';
			if ($this->hasRequestParameter('path')) {
				$pathPart = $this->getRequestParameter('path');
			}
		}

		if ($this->hasRequestParameter('title')) {
			$title = $this->getRequestParameter('title');
			$pageData->setTitle($title);
		}
		$this->entityManager->persist($pageData);

		// Set parent node
		if ( ! empty($parent)) {
			$page->moveAsLastChildOf($parent);
		}

		// Generate unused path
		//TODO: generate before "create" action
		if ($pageData instanceof Entity\PageLocalization) {
			$pathValid = false;
			$i = 2;
			$suffix = '';

			do {
				try {
					$pageData->setPathPart($pathPart . $suffix);
					$this->entityManager->flush();

					$pathValid = true;
				}
				catch (DuplicatePagePathException $pathInvalid) {
					$suffix = '-' . $i;
					$i ++;

					// Loop stopper
					if ($i > 100) {
						throw $pathInvalid;
					}
				}
			}
			while ( ! $pathValid);
		}

		$this->outputPage($pageData);
	}

	/**
	 * Called when save is performed inside the sitemap
	 */
	public function saveAction()
	{
		$this->isPostRequest();
		$this->checkLock(false);
		$pageData = $this->getPageLocalization();

		$this->checkActionPermission($pageData, Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE);

		if ($this->hasRequestParameter('title')) {
			$title = $this->getRequestParameter('title');
			$pageData->setTitle($title);
		}

		if ($this->hasRequestParameter('path')) {
			if ($pageData instanceof Entity\PageLocalization) {
				$pathPart = $this->getRequestParameter('path');

				try {
					$pageData->setPathPart($pathPart);
				}
				catch (DuplicatePagePathException $uniqueException) {

					// Clear the unit of work
					$this->entityManager->clear();

					$locale = $uniqueException->getPageLocalization()->getLocale();

					// TODO: should pass locale parameter to exception text somehow
					throw new CmsException('sitemap.error.duplicate_path', "Page with such path already exists in locale $locale");
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
	 */
	public function deleteAction()
	{
		$this->isPostRequest();

		$page = $this->getPageLocalization()
				->getMaster();
		
		$this->checkActionPermission($page, Entity\Abstraction\Entity::PERMISSION_NAME_SUPERVISE_PAGE);

		if ($page->hasChildren()) {
			throw new CmsException(null, "Cannot remove page with children");
		}

		$this->delete();
	}

	/**
	 * Called on page publish
	 */
	public function publishAction()
	{
		// Must be executed with POST method
		$this->isPostRequest();

		$this->checkActionPermission($this->getPageLocalization(), Entity\Abstraction\Entity::PERMISSION_NAME_SUPERVISE_PAGE);

		$this->checkLock();
		$this->publish();
		
		$this->unlockPage();
		
//		$auditLog = ObjectRepository::getAuditLogger($this);
//		$user = $this->getUser();
//		$auditLog->info(array(), 'Content manager', 'Publish', $user->getLogin());
	}

	/**
	 * Called on page locking action
	 */
	public function lockAction()
	{
		$this->isPostRequest();

		$this->checkActionPermission($this->getPageLocalization(), Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE);

		$this->lockPage();
	}

	/**
	 * Called on page unlock action
	 */
	public function unlockAction()
	{
		try {
			$this->checkLock();
		}
		catch (ObjectLockedException $e) {
			$this->getResponse()->setResponseData(true);
			return;
		}
		$this->unlockPage();
	}

	public function versionPreviewAction()
	{	
		$localizationId = $this->getRequestParameter('page_id');
		$revisionId = $this->getRequestParameter('version_id');
		
		$em = ObjectRepository::getEntityManager(PageController::SCHEMA_AUDIT);

		$pageLocalization = $em->getRepository(PageRequest::DATA_ENTITY)
				->findOneBy(array('id' => $localizationId, 'revision' => $revisionId));
		
		if ( ! ($pageLocalization instanceof Entity\Abstraction\Localization)) {
			throw new CmsException(null, 'Page version not found');
		}

		$controller = $this->getPageController();

		$localeId = $this->getLocale()->getId();
		$media = $this->getMedia();
		
		$request = new HistoryPageRequestView($localeId, $media);
		$request->setPageLocalization($pageLocalization);
		
		$revisionId = $pageLocalization->getRevisionId();
		$request->setRevision($revisionId);
		
		$response = $controller->createResponse($request);

		$controller->prepare($request, $response);
		$request->setDoctrineEntityManager($em);
		$controller->execute($request);

		$return = array(
			'internal_html' => $response->__toString()
		);
		
		$this->getResponse()
				->setResponseData($return);
	}
	
	/**
	 * Page duplicate action
	 */
	public function duplicateAction()
	{
		$this->checkLock(false);
		$this->duplicate();
	}
	
	/**
	 * Converts internal path to page ID
	 */
	public function pathToIdAction()
	{
		$input = $this->getRequestInput();
		$controller = $this->getPageController();
		$em = $controller->getEntityManager();
		$repo = $em->getRepository(Entity\PageLocalization::CN());
		/* @var $repo PageRepository */
		
		$path = $input->get('page_path');
		$path = trim($path, '/');
		$locale = $input->get('locale');
		
		//TODO: the locale detection from URL could differ in fact
		// Remove locale prefix
		if ($path == $locale || strpos($path, $locale . '/') === 0) {
			$path = substr($path, strlen($locale) + 1);
		}
		
		$criteria = array(
			'path' => $path,
			'locale' => $locale,
		);
		
		$pageLocalization = $repo->findOneBy($criteria);
		
		if ( ! $pageLocalization instanceof Entity\PageLocalization) {
			throw new CmsException(null, 'No page was found by the URL');
		}
		
		$pageId = $pageLocalization->getId();
		
		$this->getResponse()->setResponseData($pageId);
	}
	
}
