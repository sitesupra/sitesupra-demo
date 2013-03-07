<?php

namespace Supra\Cms\ContentManager\Page;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Controller\Pages\Request\HistoryPageRequestEdit;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Exception\DuplicatePagePathException;
use Supra\Editable;
use Supra\Cms\Exception\CmsException;
use Supra\Cms\Exception\ObjectLockedException;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\Exception\AuthorizationException;
use Supra\Response\ResponseContext;
use Doctrine\ORM\NoResultException;
use Supra\Controller\Pages\Exception\LayoutNotFound;
use Supra\Controller\Pages\BrokenBlockController;
use Supra\Uri\Path;
use Supra\Controller\Pages\Event\AuditEvents;
use Supra\Controller\Pages\Event\PageEventArgs;
use Supra\Controller\Pages\Configuration\BlockPropertyConfiguration;

/**
 * 
 */
class PageAction extends PageManagerAction
{

	/**
	 * Overriden so PHP <= 5.3.2 doesn't treat pageAction() as a constructor
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Returns all page information required for load
	 */
	public function pageAction()
	{
		$controller = $this->getPageController();
		$request = $this->getPageRequest();
		
		$localization = $this->getPageLocalization();
		$page = $request->getPage();
		
		$response = $controller->createResponse($request);
		$controller->prepare($request, $response);
		
		$this->setInitialPageId($localization->getId());
				
		$templateError = null;
		// TODO: handling?
		if ($localization->getTemplate() != null) {
			ObjectRepository::beginControllerContext($controller);
			try {
				$controller->execute($request);
			} catch (\Twig_Error_Loader $e) {
				$templateError = $e;
			} catch (LayoutNotFound $e) {
				$templateError = $e;
			} catch (\Exception $e) {
				ObjectRepository::endControllerContext($controller);
				throw $e;
			}
			ObjectRepository::endControllerContext($controller);
		} else {
			$templateError = new \RuntimeException("No template is chosen");
		}
		
		$localizationArray = $this->convertLocalizationToArray($localization);
		
		$placeHolderSet = array();
		$blockSet = new \Supra\Controller\Pages\Set\BlockSet();

		if ( ! is_null($templateError)) {
			$this->log->warn("Page could not be shown in CMS because of exception:\n", $templateError);
			$localizationArray['internal_html'] = '<h1>Page template or layout not found</h1><p>Please make sure the template is assigned and the template is published in this locale and it has layout assigned.</p>';
		}
		else {
			$localizationArray['internal_html'] = $response->__toString();
			
			$placeHolderSet = $request->getPlaceHolderSet()->getFinalPlaceHolders();
			$blockSet = $request->getBlockSet();
		}

		$responseContext = new ResponseContext();
		$this->getResponse()->setContext($responseContext);
		
		// Collecting locked blocks
		$lockedBlocks = array();
		foreach ($blockSet as $block) {
			if ($block->getLocked()) {
				$holderName = $block->getPlaceHolder()->getName();

				if ( ! isset($lockedBlocks[$holderName])) {
					$lockedBlocks[$holderName] = array();
				}

				$lockedBlocks[$holderName][] = $block;
			}
		}          
		
		$groupsData = array();

		/* @var $placeHolder Entity\Abstraction\PlaceHolder */
		foreach ($placeHolderSet as $placeHolder) {
			
			$placeHolderName = $placeHolder->getName();
			
			$groupName = null;
			$group = $placeHolder->getGroup();

			if ( ! is_null($group)) {
				
				$groupName = $group->getName();
				$groupLayout = $group->getGroupLayout();
				
				if ( ! isset($groupsData[$groupName])) {
					$groupData = array(
						'id' => $groupName,
						'closed' => false,
						'locked' => false,
						'editable' => true,
						'title' => $group->getTitle(),
						'type' => 'list_one',
						'allow' => array(),
						'layout_limit' => 4,
						'properties' => array(
							'layout' => array(
								'value' => $groupLayout, 
								'language' => null, 
								'__shared' => false
							),
						),
						'contents' => array(),
					);
					
					$groupsData[$groupName] = $groupData;
				}
			}
			
			$placeHolderData = array(
				'id' => $placeHolderName,
				'title' => $placeHolder->getTitle(),
				'type' => 'list',
				'closed' => ! $localization->isPlaceHolderEditable($placeHolder),
				'locked' => $placeHolder->getLocked(),
				//TODO: not specified now
//				'allow' => array(
//						0 => 'Project_Text_TextController',
//				),
				'contents' => array()
			);

			$blockSubset = $blockSet->getPlaceHolderBlockSet($placeHolder)
					->getArrayCopy();
			
			if ( ! $placeHolder->getLocked() && isset($lockedBlocks[$placeHolderName])) {
				$blockSubset = array_merge($blockSubset, $lockedBlocks[$placeHolderName]);
			}

			/* @var $block Entity\Abstraction\Block */
			foreach ($blockSubset as $block) {

				$controller = $block->createController();
				$configuration = $controller->getConfiguration();

				if ($controller instanceof BrokenBlockController) {
					$componentName = $configuration->class;
					$block->setComponentName($componentName);
				}

				$block->prepareController($controller, $request, $responseContext);

				$blockData = array(
					'id' => $block->getId(),
					'type' => $block->getComponentName(),
					'closed' => ! $localization->isBlockEditable($block),
					'locked' => $block->getLocked(),
					'properties' => array(),
					'owner_id' => $block->getPlaceHolder()
						->getLocalization()->getId(),
				);

				$propertyDefinition = (array) $configuration->properties;

				foreach ($propertyDefinition as $property) {

					/* @var $property BlockPropertyConfiguration */
					$propertyName = $property->name;
					$blockProperty = $controller->getProperty($propertyName);

					if ($page->isBlockPropertyEditable($blockProperty)) {

						$propertyData = $this->gatherPropertyData($controller, $property);
						$blockData['properties'][$propertyName] = $propertyData;
					}
				}
				
				$placeHolderData['contents'][] = $blockData;
			}

			if ( ! empty($groupName)) {
				// TODO: move locked parameter to group config
				if ($placeHolderData['locked']) {
					$groupsData[$groupName]['locked'] = true;
					$groupsData[$groupName]['editable'] = false;
				}
				
				$placeHolderData['type'] = 'list_one';
				$placeHolderData['editable'] = false;
				$groupsData[$groupName]['contents'][] = $placeHolderData;
			} else {
				$localizationArray['contents'][] = $placeHolderData;
			}			
		}
		
		$localizationArray['contents'] = array_merge(array_values($groupsData), $localizationArray['contents']);
		$this->getResponse()->setResponseData($localizationArray);

		// TODO: implement in CmsAction
		$this->getResponse()
				->addResponsePart('permissions', array(array('edit' => true, 'publish' => true)));
	}

	/**
	 * Creates a new page
	 * @TODO: create action for templates as well
	 */
	public function createAction()
	{
		$this->lock();

		$this->isPostRequest();

		$type = $this->getRequestParameter('type');
		$templateId = $this->getRequestParameter('template');
		$parent = $this->getPageByRequestKey('parent_id');
		$localeId = $this->getLocale()->getId();

		$this->checkActionPermission($parent, Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE);

		$eventManager = $this->entityManager->getEventManager();
		$eventManager->dispatchEvent(AuditEvents::pagePreCreateEvent);

		$page = null;
		$pathPart = null;

		// Page types
		if ($type == Entity\Abstraction\Entity::GROUP_DISCR) {
			$page = new Entity\GroupPage();
		} elseif ($type == Entity\Abstraction\Entity::APPLICATION_DISCR) {
			$page = new Entity\ApplicationPage();

			$applicationId = $this->getRequestParameter('application_id');
			$page->setApplicationId($applicationId);
		} else {
			$page = new Entity\Page();
		}

		$rootPage = empty($parent);

		$pageData = Entity\Abstraction\Localization::factory($page, $localeId);

		$this->entityManager->persist($page);

		// Template ID
		if ($pageData instanceof Entity\PageLocalization) {
			$template = $this->entityManager->find(Entity\Template::CN(), $templateId);
			/* @var $template Supra\Controller\Pages\Entity\Template */

			if (empty($template)) {
				throw new CmsException(null, "Template not specified or found");
			}

			$templateLocalization = $template->getLocalization($localeId);

			if ($templateLocalization instanceof Entity\TemplateLocalization) {
				$pageData->setIncludedInSearch($templateLocalization->isIncludedInSearch());
				$pageData->setVisibleInMenu($templateLocalization->isVisibleInMenu());
				$pageData->setVisibleInSitemap($templateLocalization->isVisibleInSitemap());
			}

			$pageData->setTemplate($template);

			if ( ! $rootPage) {
				if ( ! $this->hasRequestParameter('path')) {
					throw new CmsException(null, 'Page path can not be empty');
				}

				$pathPart = $this->getRequestParameter('path');
			}

			if ( ! $this->hasRequestParameter('title')) {
				throw new CmsException(null, 'Page title can not be empty');
			}
		}

		if ($this->hasRequestParameter('title')) {
			$title = $this->getRequestParameter('title');
			$pageData->setTitle($title);
		}
		$this->entityManager->persist($pageData);

		// Change path
		if ($pageData instanceof Entity\PageLocalization) {
			if ( ! $rootPage) {
				$pageData->setPathPart($pathPart);
			} else {
				// Must create root path for the root page
				$rootPath = $pageData->getPathEntity();
				$rootPath->setPath('');
				$pageData->setPathPart('');
			}
		}

		// Set parent node
		if ( ! $rootPage) {
			try {
				$page->moveAsLastChildOf($parent);
			} catch (DuplicatePagePathException $e) {
				throw new CmsException(null, $e->getMessage(), $e);
			} catch (\Exception $e) {
				if ($this->entityManager->isOpen()) {
					$this->entityManager->remove($page);
					$this->entityManager->remove($pageData);
					$this->entityManager->flush();
				}

				throw $e;
			}
		}

		$this->entityManager->flush();
		$this->unlock();

		$this->writeAuditLog('%item% created', $pageData);

		if ( ! $page instanceof Entity\GroupPage) {
			$request = PageRequestEdit::factory($pageData);
			$request->setDoctrineEntityManager($this->entityManager);
			$request->getPlaceHolderSet();
			$request->createMissingPlaceHolders();
			$request->createMissingBlockProperties();
		}

		$this->outputPage($pageData);

		// this will create page base copy (similar one, that is created on page publish action)
		// which will be used as build base for page change-history displaying
		$pageEventArgs = new PageEventArgs();
		$pageEventArgs->setProperty('referenceId', $pageData->getId());
		$pageEventArgs->setEntityManager($this->entityManager);
		$eventManager->dispatchEvent(AuditEvents::pagePostCreateEvent, $pageEventArgs);
	}

	/**
	 * Called when save is performed inside the sitemap
	 */
	public function saveAction()
	{
		$this->isPostRequest();
		$this->checkLock();
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
				} catch (DuplicatePagePathException $uniqueException) {

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
			$template = $this->entityManager->find(Entity\Template::CN(), $templateId);
			$pageData->setTemplate($template);
		}

		$this->entityManager->flush();
		$this->outputPage($pageData);

		$this->writeAuditLog('%item% saved', $pageData);
	}

	/**
	 * Called when page delete is requested
	 */
	public function deleteAction()
	{
		$this->lock();

		$this->isPostRequest();

		$page = $this->getPageLocalization()
				->getMaster();

		$this->checkActionPermission($page, Entity\Abstraction\Entity::PERMISSION_NAME_SUPERVISE_PAGE);

		if ($page->hasChildren()) {
			throw new CmsException(null, "Cannot remove page with children");
		}

		$this->delete();
		$this->unlock();

		$this->writeAuditLog('%item% deleted', $page);
	}

	/**
	 * Called on page publish
	 */
	public function publishAction()
	{
		// Must be executed with POST method
		$this->isPostRequest();
		$pageLocalization = $this->getPageLocalization();

		$this->checkActionPermission($pageLocalization, Entity\Abstraction\Entity::PERMISSION_NAME_SUPERVISE_PAGE);

		$this->checkLock();
		$this->publish();

		$this->unlockPage();

		$this->writeAuditLog('%item% published', $pageLocalization);
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
		} catch (ObjectLockedException $e) {
			$this->getResponse()->setResponseData(true);
			return;
		}
		$this->unlockPage();
	}

	public function versionPreviewAction()
	{
		$localizationId = $this->getRequestParameter('page_id');
		$revisionId = $this->getRequestParameter('version_id');

		$localization = $this->findLocalizationInAudit($localizationId, $revisionId);

		$controller = $this->getPageController();

		$localeId = $this->getLocale()->getId();
		$media = $this->getMedia();

		$request = new HistoryPageRequestEdit($localeId, $media);
		$request->setPageLocalization($localization);
		$request->setDoctrineEntityManager($this->entityManager);

		$response = $controller->createResponse($request);

		$controller->prepare($request, $response);

		$e = null;
		ObjectRepository::beginControllerContext($controller);
		try {
			$controller->execute($request);
		} catch (\Exception $e) {
			
		}

		ObjectRepository::endControllerContext($controller);

		if ($e instanceof \Exception) {
			throw $e;
		}

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
		$this->lock();

		$this->isPostRequest();
		$localization = $this->getPageLocalization();
		$master = $localization->getMaster();
		$this->duplicate($localization);
		$this->unlock();

		$this->writeAuditLog('%item% duplicated', $master);
	}

	/**
	 * Create localization
	 */
	public function createLocalizationAction()
	{
		$this->isPostRequest();
		$this->createLocalization();
	}

	/**
	 * Converts internal path to page ID
	 */
	public function pathToIdAction()
	{
		$input = $this->getRequestInput();
		$controller = $this->getPageController();
		$em = $controller->getEntityManager();
		$localizationEntity = Entity\PageLocalization::CN();

		$path = $input->get('page_path');
		$path = parse_url($path, PHP_URL_PATH);
		$path = trim($path, '/');
		$localeId = null;

		$path = new Path($path);
		$localeManager = ObjectRepository::getLocaleManager($this);
		$locales = $localeManager->getLocales();

		foreach ($locales as $locale) {
			/* @var $locale LocaleInterface */

			$_localeId = $locale->getId();
			$pathPrefix = new Path($_localeId);

			if ($path->startsWith($pathPrefix)) {
				$path->setBasePath($pathPrefix);
				$localeId = $_localeId;
				break;
			}
		}

		if (is_null($localeId)) {
			$localeId = $input->get('locale');
		}

		$pagePath = $path->getPath(Path::FORMAT_NO_DELIMITERS);

		$criteria = array(
			'path' => $pagePath,
			'locale' => $localeId,
		);

		try {
			$pageLocalization = $em->createQuery("SELECT l FROM $localizationEntity l JOIN l.path p
					WHERE p.path = :path AND l.locale = :locale")
					->setParameters($criteria)
					->getSingleResult();

			$pageId = $pageLocalization->getId();

			$redirectData = $this->getPageController()->getRedirectData($pageLocalization);

			$response = array(
				'redirect' => $redirectData['redirect'],
				'redirect_page_id' => $redirectData['redirect_page_id'],
				'page_id' => $pageId,
				'locale' => $localeId,
			);

			$this->getResponse()->setResponseData($response);
		} catch (NoResultException $noResult) {

			return;
		}
	}

	/**
	 * Returns all layouts
	 */
	public function layoutsAction()
	{
		$this->getResponse()->setResponseData($this->getLayouts());
	}

	protected function getLayouts()
	{
		$layouts = array();

		$themeProvider = ObjectRepository::getThemeProvider($this);

		$currentTheme = $themeProvider->getCurrentTheme();

		$themeLayouts = $currentTheme->getLayouts();

		foreach ($themeLayouts as $themeLayout) {
			/* @var $themeLayout ThemeLayout */

			$layouts[] = array(
				'id' => $themeLayout->getName(),
				'title' => $themeLayout->getTitle()
			);
		}

		return $layouts;
	}

	/**
	 * @param BlockController $blockController
	 * @param BlockPropertyConfiguration $property
	 * @param string $parentName
	 * @return array
	 */
	protected function gatherPropertyData($blockController, $property)
	{
		$propertyName = $property->name;

		$blockProperty = $blockController->getProperty($propertyName);

		$editable = $blockProperty->getEditable();
		$propertyValue = $editable->getContentForEdit();
		$metadataCollection = $blockProperty->getMetadata();
		$data = array();
		
		/* @var $metadata Entity\BlockPropertyMetadata */
		foreach ($metadataCollection as $name => $metadata) {

			$data[$name] = array();

			$referencedElement = $metadata->getReferencedElement();
			
			$elementData = $this->convertReferencedElementToArray($referencedElement);
			if ($editable instanceof Editable\Gallery) {
				$data[$name] = array(
					'id' => $elementData['imageId'],
					'image' => $elementData,
				);
			} else {
				$data[$name] = $elementData;
			}

			$data[$name]['__meta__'] = $metadata->getId();
		}

		$propertyData = $propertyValue;

		if ($editable instanceof Editable\Html) {
			$propertyData = array(
				'html' => $propertyValue['html'],
				'data' => $data
			);
		}

		if ($editable instanceof Editable\Link || $editable instanceof Editable\Video) {
			if (isset($data[0])) {
				$propertyData = $data[0];
			}
		}
		
		if ($editable instanceof Editable\InlineMedia) {
			if (isset($data[0])) {
				$propertyData = $data[0];
			}
		}

		if ($editable instanceof Editable\Image) {
			if ($propertyValue) {
				$fileStorage = ObjectRepository::getFileStorage($this);
				$image = $fileStorage->getDoctrineEntityManager()
						->find(\Supra\FileStorage\Entity\Image::CN(), $propertyValue);

				if ($image instanceof \Supra\FileStorage\Entity\Image) {
					$propertyData = $fileStorage->getFileInfo($image);
				}
			}
		}

		if ($editable instanceof Editable\File) {
			if ($propertyValue) {
				$fileStorage = ObjectRepository::getFileStorage($this);
				$file = $fileStorage->getDoctrineEntityManager()
						->find(\Supra\FileStorage\Entity\File::CN(), $propertyValue);
				if ($file instanceof \Supra\FileStorage\Entity\File) {
					$propertyData = $fileStorage->getFileInfo($file);
				}
			}
		}
		
		if ($editable instanceof Editable\BlockBackground) {

			$classname = null;
			$imageData = null;

			if ($blockProperty->getMetadata()->containsKey('image')) {

				$imageReferencedElement = $blockProperty->getMetadata()->get('image')->getReferencedElement();

				$imageId = $imageReferencedElement->getImageId();

				$fileStorage = ObjectRepository::getFileStorage($this);

				$image = $fileStorage->getDoctrineEntityManager()
						->find(\Supra\FileStorage\Entity\Image::CN(), $imageId);

				if ( ! empty($image)) {
					$imageData = $imageReferencedElement->toArray();
					$imageData['image'] = $fileStorage->getFileInfo($image);
				}
			} else {
				$classname = $blockProperty->getValue();
			}

			$propertyData = array('image' => $imageData, 'classname' => $classname);
		}

		if ($editable instanceof Editable\Gallery) {

			$galleryController = $editable->getDummyBlockController();
			$galleryController->setRequest($this->getPageRequest());

			foreach ($metadataCollection as $name => $metadata) {

				$subProperties = array();
				$galleryController->setParentMetadata($metadata);

				foreach ($property->properties as $subPropertyDefinition) {
					$subProperties[$subPropertyDefinition->name] = $this->gatherPropertyData($galleryController, $subPropertyDefinition);
				}

				$data[$name] = $data[$name]
						+ array('properties' => $subProperties);
			}

			ksort($data);
			$propertyData = array_values($data);
		}
		
		//
		if ($editable instanceof Editable\Slideshow) {
			$propertyData = $this->prepareSlideshowProperties($blockProperty, $property);
		}
		
		$propertyInfo = array(
			'__shared__' => false,
			'value' => $propertyData,
			'language' => null,
		);

		if ($blockProperty instanceof Entity\SharedBlockProperty) {
			$propertyInfo['__shared__'] = true;
			$propertyInfo['locale'] = $blockProperty->getOriginalLocalization()
					->getLocale();
		}

		//TODO: sub-properties are not prepared to be non-/shared
		if ($blockController instanceof \Supra\Controller\Pages\GalleryBlockController) {
			$propertyInfo = $propertyData;
		}

		return $propertyInfo;
	}
	
	/**
	 * @FIXME
	 * 
	 * @param type $property
	 * @param type $configuration
	 */
	private function prepareSlideshowProperties($property, $configuration)
	{
		$editable = $property->getEditable();
		$propertyValue = $editable->getContentForEdit();
		
		foreach ($propertyValue as &$slideData) {
			foreach ($configuration->properties as $propertyConfiguration) {

				$name = $propertyConfiguration->name;
				$propertyEditable = $propertyConfiguration->editableInstance;

				if (isset($slideData[$name])) {
					/* @var $propertyEditable \Supra\Editable\EditableInterface */
					$propertyEditable->setContent($slideData[$name]);
					$editableContent = $propertyEditable->getContentForEdit();
					
					if ($propertyEditable instanceof Editable\InlineMedia
							|| $propertyEditable instanceof Editable\Image
							|| $propertyEditable instanceof Editable\BlockBackground) {
						
						$slideData[$name] = $editableContent;
					}
				}
			}
		}
		
		return $propertyValue;	
	}
	
	/**
	 * 
	 * @param \Supra\Controller\Pages\Entity\Abstraction\Localization $localization
	 * @return boolean
	 */
	private function isAllowedToEditLocalization(Entity\Abstraction\Localization $localization)
	{
		$page = $localization->getMaster();
		
		$allowed = true;
		
		if ($page instanceof Entity\Template) {
			try {
				$allowed = $this->checkApplicationAllAccessPermission();
			} catch (AuthorizationException $e) {
				$allowed = false;
			}
		} 
		else {
			try {
				$this->checkActionPermission($page, Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE);
			} catch (AuthorizationException $e) {

				try {
					$this->checkActionPermission($localization, Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE);
				} catch (AuthorizationException $e) {
					$allowed = false;
				}
			}
		}
		
		return $allowed;
	}
	
	/**
	 * 
	 * @param \Supra\Controller\Pages\Entity\Abstraction\Localization $localization
	 * @return array
	 */
	private function convertLocalizationToArray(Entity\Abstraction\Localization $localization)
	{
		$page = $localization->getMaster();
		$isTemplateInstance = ($page instanceof Entity\Template);
		
		$localizationId = $localization->getId();
		$localeId = $localization->getLocale();
		
		// defaults
		$pathPart = null;
		$pathPrefix = null;
		$templateArray = array();
		$scheduledDate = null;
		$scheduledTime = null;
		$metaKeywords = null;
		$metaDescription = null;
		$isActive = true;
		$redirect = null;
		$createdDate = null;
		$createdTime = null;
		$isLimited = null;
		$hasLimitedParent = null;
		$locked = false;
		$changeFrequency = null;
		$priority = null;
		$layoutName = null;
		$layouts = null;
		
		if ( ! $isTemplateInstance) {
			/* @var $localization Entity\PageLocalization */
			
			// collecting localization template data
			$template = $localization->getTemplate();
			if ($template instanceof Entity\Template) {
				
				$templateLocalization = $template->getLocalization($localeId);

				if ($templateLocalization instanceof Entity\TemplateLocalization) {
					$templateArray = array(
						'id' => $template->getId(),
						'title' => $templateLocalization->getTitle(),
						// @FIXME: hardcoded value
						'img' => '/cms/lib/supra/img/templates/template-3-small.png',
					);
				} else {
					$templateError = new \RuntimeException("No template localization was found");
					$this->log->error("No template {$localeId} localization was found for localization #{$localizationId}");
				}
			} else {
				$templateError = new \RuntimeException("No template entity was assigned or found");
				$this->log->error("No template entity was found for localization #{$localizationId}");
			}
			
			// collecting path data
			$pathPart = $localization->getPathPart();
			if ($page->hasParent()) {
				$pathPrefix = $page->getParent()->getPath();
			}

			// redirect object, if set
			$redirect = $localization->getRedirect();
			if ($redirect instanceof Entity\ReferencedElement\LinkReferencedElement) {
				$redirect = $this->convertReferencedElementToArray($redirect);
			}
			
			// publish schedule, if set
			$scheduledDateTime = $localization->getScheduleTime();
			if ($scheduledDateTime instanceof \DateTime) {
				$scheduledDate = $scheduledDateTime->format('Y-m-d');
				$scheduledTime = $scheduledDateTime->format('H:i:s');
			}
			
			// publish time, could be null
			if ($localization->isPublishTimeSet()) {
				$createdDateTime = $localization->getCreationTime();
				$createdDate = $createdDateTime->format('Y-m-d');
				$createdTime = $createdDateTime->format('H:i:s');
			}
			
			$metaKeywords = $localization->getMetaKeywords();
			$metaDescription = $localization->getMetaDescription();
			$isActive = $localization->isActive();
			
			// SEO
			$changeFrequency = $localization->getChangeFrequency();
			$priority = $localization->getPagePriority();

//			// TODO: this functionality isn't working right now
//			$isLimited = $localization->isLimitedAccessPage();
//			$hasLimitedParent = false;
//			
//			$parent = $localization->getParent();
//			while ( ! is_null($parent)) {
//				$hasLimitedParent = $parent->getPathEntity()->isLimited();
//				if ($hasLimitedParent) {
//					break;
//				}
//				$parent = $parent->getParent();
//			}			
		}

		if ($isTemplateInstance) {
			if ($page->hasLayout($this->getMedia())) {
				$layout = $page->getLayout($this->getMedia());
				$layoutName = $layout->getName();
			}
			$layouts = $this->getLayouts();
		}
		
		// Comparing published version (if any) revision with draft 
		$publicEm = ObjectRepository::getEntityManager('#public');
		$publishedData = $publicEm->find(Entity\Abstraction\Localization::CN(), $localizationId);
		$isPublished = false;
		if ($publishedData !== null) {
			$isPublished = ($localization->getRevisionId() == $publishedData->getRevisionId());
		}

		// Collecting all available localizations
		$localizationsData = array();
		$localizations = $page->getLocalizations();
		foreach ($localizations as $localization) {
			/* @var $localization Entity\Abstraction\Localization */
			$localizationsData[$localization->getLocale()] = array(
				'page_id' => $localization->getId()
			);
		}

		// Page lock
		$lock = $localization->getLock();
		if ($lock !== null) {
			$lockOwner = $lock->getUserId();
			$currentUser = $this->getUser();
			
			if ($currentUser->getId() == $lockOwner) {
				$locked = array(
					'userlogin' => $this->getUser()->getLogin(),
				);
			}
		}

		$ancestors = $localization->getAncestors();
		$ancestorIds = \Supra\Database\Entity::collectIds($ancestors);

		$array = array(
			'id' => $localizationId,
			'master_id' => $page->getId(),
			'root' => $page->isRoot(),
			'revision_id' => $localization->getRevisionId(),
			'locale' => $localeId,
			'title' => $localization->getTitle(),
			'path' => $pathPart,
			'path_prefix' => $pathPrefix,
			'template' => $templateArray,
			'type' => $isTemplateInstance ? 'template' : 'page',
			'keywords' => $metaKeywords,
			'description' => $metaDescription,
			'scheduled_date' => $scheduledDate,
			'scheduled_time' => $scheduledTime,
			'redirect' => $redirect,
			'active' => $isActive,
			'is_limited' => $isLimited,
			'has_limited_parent' => $hasLimitedParent,
			'created_date' => $createdDate,
			'created_time' => $createdTime,
			'global' => $page->getGlobal(),
			'localizations' => $localizationsData,
			'allow_edit' => $this->isAllowedToEditLocalization($localization),
			'is_visible_in_menu' => $localization->isVisibleInMenu(),
			'is_visible_in_sitemap' => $localization->isVisibleInSitemap(),
			'include_in_search' => $localization->isIncludedInSearch(),
			'published' => $isPublished,
			'lock' => $locked,
			'tree_path' => $ancestorIds,
			'page_change_frequency' => $changeFrequency,
			'page_priority' => $priority,
			'layout' => $layoutName,
			'layouts' => $layouts,
			
			'internal_html' => null,
			'contents' => array(),
		);
		
		return $array;
	}
}
