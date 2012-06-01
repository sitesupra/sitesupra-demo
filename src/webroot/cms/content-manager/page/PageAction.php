<?php

namespace Supra\Cms\ContentManager\Page;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Controller\Pages\Request\HistoryPageRequestEdit;
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
use Supra\Response\ResponseContext;
use Doctrine\ORM\NoResultException;
use Supra\Controller\Pages\Exception\LayoutNotFound;
use Supra\Controller\Pages\BrokenBlockController;
use Supra\Uri\Path;
use Supra\Locale\Locale;
use Supra\Controller\Pages\Entity\PageRevisionData;
use Supra\Controller\Pages\Event\AuditEvents;
use Supra\Controller\Pages\Event\PageEventArgs;
use Supra\Controller\Pages\Configuration\BlockPropertyConfiguration;
use Supra\Controller\Pages\Entity\ThemeLayout;
use Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;

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
		$pageData = $this->getPageLocalization();

		// Use page localization locale, still current CmsAction locale should be the same already
		$localeId = $pageData->getLocale();

		$pageId = $pageData->getId();

		// Create special request
		$request = $this->getPageRequest();

		$response = $controller->createResponse($request);
		$controller->prepare($request, $response);

		$page = $pageData->getMaster();

		// Can this really happen?
		if (empty($page)) {
			$this->getResponse()
					->setErrorMessage("Page does not exist");

			return;
		}

		$this->setInitialPageId($pageId);

		$isAllowedEditing = true;

		if ($page instanceof Entity\Template) {

			try {
				$isAllowedEditing = $this->checkApplicationAllAccessPermission();
			} catch (AuthorizationException $e) {
				$isAllowedEditing = false;
			}
		} else {

			try {
				$this->checkActionPermission($page, Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE);
			} catch (AuthorizationException $e) {

				try {
					$this->checkActionPermission($pageData, Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE);
				} catch (AuthorizationException $e) {
					$isAllowedEditing = false;
				}
			}
		}

		$request->setPageLocalization($pageData);
		$templateError = null;

		// TODO: handling?
		if ($pageData->getTemplate() != null) {
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
		$isLimited = null;
		$hasLimitedParent = null;

		//TODO: create some path for templates also (?)
		if ($page instanceof Entity\Page) {

			/* @var $pageData Entity\PageLocalization */
			$pathPart = $pageData->getPathPart();

			if ($page->hasParent()) {
				$pathPrefix = $page->getParent()
						->getPath();
			}

			$template = $pageData->getTemplate();

			if ($template instanceof Entity\Template) {
				$templateData = $template->getLocalization($localeId);

				if ($templateData instanceof Entity\TemplateLocalization) {
					$templateArray = array(
						'id' => $template->getId(),
						'title' => $templateData->getTitle(),
						//TODO: hardcoded
						'img' => '/cms/lib/supra/img/templates/template-3-small.png',
					);
				} else {
					$templateError = new \RuntimeException("No template localization was found");
					//TODO: warn
//					throw new \Supra\Controller\Pages\Exception\RuntimeException("Template doesn't exist for page $page in locale $localeId");
				}
			} else {
				$templateError = new \RuntimeException("No template entity was assigned or found");
				//TODO: warn
			}

			$scheduledDateTime = $pageData->getScheduleTime();
			$redirectLink = $pageData->getRedirect();
			$metaKeywords = $pageData->getMetaKeywords();
			$metaDescription = $pageData->getMetaDescription();
			$active = $pageData->isActive();

			if ($pageData instanceof Entity\PageLocalization) {
				$isLimited = $pageData->isLimitedAccessPage();

				$hasLimitedParent = false;
				$parent = $pageData->getParent();
				while ( ! is_null($parent)) {
					if ($parent instanceof Entity\PageLocalization) {
						$hasLimitedParent = $parent->getPathEntity()
								->isLimited();

						break;
					}

					$parent = $parent->getParent();
				}
			}

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

		$publicEm = ObjectRepository::getEntityManager('#public');
		$publishedData = $publicEm->find(Entity\Abstraction\Localization::CN(), $pageData->getId());
		$isPublished = false;
		if ($publishedData instanceof Entity\Abstraction\Localization) {
			$isPublished = ($pageData->getRevisionId() == $publishedData->getRevisionId());
		}

		$pageLocalizationArray = array();
		$pageLocalizations = $page->getLocalizations();

		foreach ($pageLocalizations as $localization) {
			/* @var $localization Entity\Abstraction\Localization */
			$pageLocalizationArray[$localization->getLocale()] = array(
				'page_id' => $localization->getId()
			);
		}

		$array = array(
			'id' => $pageData->getId(),
			'master_id' => $page->getId(),
			'revision_id' => $pageData->getRevisionId(),
			'locale' => $localeId,
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
			'is_limited' => $isLimited,
			'has_limited_parent' => $hasLimitedParent,
			'created_date' => $createdDate,
			'created_time' => $createdTime,
			'global' => $page->getGlobal(),
			'localizations' => $pageLocalizationArray,
			'allow_edit' => $isAllowedEditing,
			'is_visible_in_menu' => $pageData->isVisibleInMenu(),
			'is_visible_in_sitemap' => $pageData->isVisibleInSitemap(),
			'include_in_search' => $pageData->isIncludedInSearch(),
			'published' => $isPublished,
		);

		if ( ! is_null($templateError)) {
			$this->log->warn("Page could not be shown in CMS because of exception:\n", $templateError);

			$array['internal_html'] = '<h1>Page template or layout not found</h1><p>Please make sure the template is assigned and the template is published in this locale and it has layout assigned.</p>';
		}

		if ($page instanceof Entity\Page) {
			$array['page_change_frequency'] = $pageData->getChangeFrequency();
			$array['page_priority'] = $pageData->getPagePriority();
		}

		if ($page instanceof Entity\Template) {

			$layoutName = null;

			if ($page->hasLayout($this->getMedia())) {

				$layout = $page->getLayout($this->getMedia());

				$layoutName = $layout->getName();
			}

			$array['layout'] = $layoutName;

			// fetch all layouts
			$array['layouts'] = $this->getLayouts();
		}

		$array['root'] = $page->isRoot();

		$page = $request->getPage();

		$placeHolderSet = array();
		$blockSet = new \Supra\Controller\Pages\Set\BlockSet();

		if (is_null($templateError)) {
			$placeHolderSet = $request->getPlaceHolderSet()
					->getFinalPlaceHolders();
			$blockSet = $request->getBlockSet();
		}

		$responseContext = new ResponseContext();

		$this->getResponse()->setContext($responseContext);

		/* @var $placeHolder Entity\Abstraction\PlaceHolder */
		foreach ($placeHolderSet as $placeHolder) {

			$placeHolderData = array(
				'id' => $placeHolder->getName(),
				'title' => $placeHolder->getTitle(),
				'type' => 'list',
				'closed' => ! $pageData->isPlaceHolderEditable($placeHolder),
				'locked' => $placeHolder->getLocked(),
				//TODO: not specified now
//				'allow' => array(
//						0 => 'Project_Text_TextController',
//				),
				'contents' => array()
			);

			$blockSubset = $blockSet->getPlaceHolderBlockSet($placeHolder);

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
					'closed' => ! $pageData->isBlockEditable($block),
					'locked' => $block->getLocked(),
					'properties' => array(),
				);

				$propertyDefinition = $configuration->properties;

				foreach ($propertyDefinition as $property) {

					/* @var $property BlockPropertyConfiguration */
					$propertyName = $property->name;

					$blockProperty = $controller->getProperty($propertyName);

					if ($page->isBlockPropertyEditable($blockProperty)) {

						$editable = $blockProperty->getEditable();

						$propertyValue = $editable->getContentForEdit();
						$metadataCollection = $blockProperty->getMetadata();
						$data = array();

						/* @var $metadata Entity\BlockPropertyMetadata */
						foreach ($metadataCollection as $name => $metadata) {
							$referencedElement = $metadata->getReferencedElement();
							$data[$name] = $this->convertReferencedElementToArray($referencedElement);
						}

						$propertyData = $propertyValue;

						if ($editable instanceof Editable\Html) {
							$propertyData = array(
								'html' => $propertyValue,
								'data' => $data
							);
						}

						if ($editable instanceof Editable\Link) {
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
									$propertyData = $fileStorage->getFileInfo($image, $localeId);
								}
							}
						}

						if ($editable instanceof Editable\Gallery) {
							ksort($data);
							$propertyData = array_values($data);
						}

						$propertyInfo = array(
							'value' => $propertyData,
							'shared' => false,
							'language' => null,
						);

						if ($blockProperty instanceof Entity\SharedBlockProperty) {
							$propertyInfo['shared'] = true;
							$propertyInfo['locale'] = $blockProperty->getOriginalLocalization()
									->getLocale();
						}

						$blockData['properties'][$propertyName] = $propertyInfo;
					}
				}

				$placeHolderData['contents'][] = $blockData;
			}

			$array['contents'][] = $placeHolderData;
		}

		$this->getResponse()->setResponseData($array);

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

		$this->writeAuditLog('%item% created', $pageData);

		$request = PageRequestEdit::factory($pageData);
		$request->setDoctrineEntityManager($this->entityManager);
		$request->getPlaceHolderSet();
		$request->createMissingPlaceHolders();

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
		$this->isPostRequest();

		$page = $this->getPageLocalization()
				->getMaster();

		$this->checkActionPermission($page, Entity\Abstraction\Entity::PERMISSION_NAME_SUPERVISE_PAGE);

		if ($page->hasChildren()) {
			throw new CmsException(null, "Cannot remove page with children");
		}

		$this->delete();

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
		$this->isPostRequest();
		$localization = $this->getPageLocalization();
		$master = $localization->getMaster();
		$this->duplicate($localization);

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
			/* @var $locale Locale */

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

			$redirectData = $this->getRedirectData($pageLocalization);

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

}
