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
use Supra\Controller\Pages\Entity\ReferencedElement;
use Supra\FileStorage\Entity\Image;
use Supra\FileStorage\Entity\File;
use Supra\Cms\Exception\ObjectLockedException;
use Supra\User\Entity\User;
use Supra\Cms\Exception\CmsException;
use Supra\Uri\Path;
use Supra\Controller\Pages\Application\PageApplicationCollection;
use Supra\Controller\Pages\Request\HistoryPageRequestEdit;
use Supra\Controller\Pages\Event\CmsPagePublishEventArgs;
use Supra\Loader\Loader;
use Supra\Controller\Pages\Listener\EntityAuditListener;
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\Controller\Pages\Entity\PageRevisionData;
use Supra\Controller\Pages\Entity\Abstraction\AbstractPage;
use Supra\Controller\Pages\Entity\Page;
use Supra\Controller\Pages\Entity\Template;
use Supra\AuditLog\AuditLogEvent;
use Supra\Controller\Pages\Event\CmsPageDeleteEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Supra\Controller\Pages\Listener\PagePathGenerator;
use Supra\Controller\Pages\Event\PageEventArgs;
use Supra\Controller\Pages\Event\AuditEvents;
use Supra\Controller\Pages\Listener\EntityRevisionSetterListener;
use Supra\Controller\Pages\Event\CmsPageEventArgs;
use Supra\Controller\Pages\Exception\DuplicatePagePathException;
use Supra\Controller\Pages\Event\SetAuditRevisionEventArgs;
use Supra\Controller\Pages\Exception\MissingResourceOnRestore;

/**
 * Controller containing common methods
 */
abstract class PageManagerAction extends CmsAction
{
	const INITIAL_PAGE_ID_COOKIE = 'cms_content_manager_initial_page_id';

	const PAGE_CONTROLLER_CLASS = 'Supra\Controller\Pages\PageController';

	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @var Entity\Abstraction\Localization
	 */
	protected $pageData;

	/**
	 * @var PageController
	 */
	private $pageController;

	/**
	 * Assign entity manager
	 */
	public function __construct()
	{
		parent::__construct();

		// Will fetch connection for drafts
		$this->entityManager = ObjectRepository::getEntityManager($this);
	}

	/**
	 * TODO: must return configurable controller instance (use repository?)
	 * @return string
	 */
	protected function getPageControllerClass()
	{
		return 'Supra\Controller\Pages\PageController';
	}

	/**
	 * Get public entity manager
	 * @return EntityManager
	 */
	protected function getPublicEntityManager()
	{
		return ObjectRepository::getEntityManager($this->getPageControllerClass());
	}

	/**
	 * Get page controller instance
	 * @return PageController
	 */
	protected function getPageController()
	{
		if (is_null($this->pageController)) {
			$controllerClass = $this->getPageControllerClass();
			$this->pageController = Loader::getClassInstance($controllerClass, self::PAGE_CONTROLLER_CLASS);

			// Override to use the draft repository objects
			ObjectRepository::setCallerParent($this->pageController, $this);
		}

		return $this->pageController;
	}

	/**
	 * @param Localization $pageLocalization
	 * @return PageRequestEdit
	 */
	protected function getPageRequest(Localization $pageLocalization = null)
	{
		$controller = $this->getPageController();
		$media = $this->getMedia();
		$user = $this->getUser();

		if (is_null($pageLocalization)) {
			$pageLocalization = $this->getPageLocalization();
		}

		$request = PageRequestEdit::factory($pageLocalization, $media);
		$response = $controller->createResponse($request);

		$controller->prepare($request, $response);

		$request->setUser($user);

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
	 *
	 * @return Entity\Abstraction\AbstractPage
	 */
	protected function getPage()
	{
		$page = null;

		if (isset($this->pageData)) {
			$page = $this->pageData->getMaster();
		} else {
			$page = $this->getPageByRequestKey('page_id');
		}

		return $page;
	}

	/**
	 * @return Entity\Abstraction\Localization
	 * @throws ResourceNotFoundException
	 */
	protected function getPageLocalization()
	{
		// FIXME: using requested locale would make more common sense with global/local switch
		if (isset($this->pageData)) {
			return $this->pageData;
		}

		$this->pageData = $this->getPageLocalizationByRequestKey('page_id');

		if (empty($this->pageData)) {
			$pageId = $this->getRequestParameter('page_id');
			throw new CmsException('sitemap.error.page_not_found', "Page data for page {$pageId} not found");
		}

		$localizationId = $this->pageData->getId();

		$pageEventArgs = new \Supra\Controller\Pages\Event\PageEventArgs();
		$pageEventArgs->setProperty('referenceId', $localizationId);

		$this->entityManager->getEventManager()
				->dispatchEvent(\Supra\Controller\Pages\Event\AuditEvents::pagePreEditEvent, $pageEventArgs);

		// Handle issue when page requested with wrong locale
		$pageLocaleId = $this->pageData->getLocale();
		$expectedLocaleId = $this->getLocale()->getId();

		/*
		 * Set the system current locale if differs from 'locale' parameter received.
		 * This is done for BACK button to work after navigating to page with different language.
		 * NB! this change won't be saved in the currrent locale browser cookie storage.
		 */
		if ($expectedLocaleId != $pageLocaleId) {
			ObjectRepository::getLocaleManager($this)
					->setCurrent($pageLocaleId);
		}

		return $this->pageData;
	}

	/**
	 * Try selecting abstract page by request parameter
	 * @param string $key
	 * @return Entity\Abstraction\AbstractPage
	 */
	private function searchPageByRequestKey($key)
	{
		$pageId = $this->getRequestParameter($key);

		if (empty($pageId)) {
			return null;
		}

		$page = $this->entityManager->find(
				Entity\Abstraction\AbstractPage::CN(), $pageId);

		return $page;
	}

	/**
	 * Try selecting page localization by request parameter
	 * @param string $key
	 * @return Entity\Abstraction\Localization
	 */
	private function searchLocalizationByRequestKey($key)
	{
		$pageId = $this->getRequestParameter($key);

		// Fix for news application filter folders
		if (strpos($pageId, '_') !== false) {
			$pageId = strstr($pageId, '_', true);
		}

		if (empty($pageId)) {
			return null;
		}

		$localization = $this->entityManager->find(
				Entity\Abstraction\Localization::CN(), $pageId);

		return $localization;
	}

	/**
	 * Try loading page by searching for received page/localization ID
	 * @param string $key
	 * @return Entity\Abstraction\AbstractPage
	 */
	protected function getPageByRequestKey($key)
	{
		$page = $this->searchPageByRequestKey($key);

		if (is_null($page)) {
			$localization = $this->searchLocalizationByRequestKey($key);

			if ( ! is_null($localization)) {
				$page = $localization->getMaster();
			}
		}

		return $page;
	}

	/**
	 * Try loading localization by searching for received page/localization ID
	 * @param string $key
	 * @return Entity\Abstraction\Localization
	 */
	protected function getPageLocalizationByRequestKey($key)
	{
		$localization = $this->searchLocalizationByRequestKey($key);

		if (is_null($localization)) {
			$page = $this->searchPageByRequestKey($key);

			if ( ! is_null($page)) {
				$locale = $this->getLocale();
				$localization = $page->getLocalization($locale);
			}
		}

		return $localization;
	}

	/**
	 * @param string $localizationId
	 * @param string $revisionId
	 * @return Localization
	 */
	protected function findLocalizationInAudit($localizationId, $revisionId)
	{
		$auditEm = ObjectRepository::getEntityManager(PageController::SCHEMA_AUDIT);

		$auditEventManager = $auditEm->getEventManager();
		$setAuditRevisionEventArgs = new SetAuditRevisionEventArgs($revisionId);
		$auditEventManager->dispatchEvent(AuditEvents::setAuditRevision, $setAuditRevisionEventArgs);

		// localization revision search, for cases when the item change didn't bubble up (it was bug)
		$localizationCn = Localization::CN();
		$localizationRevisionId = $auditEm->createQuery("SELECT MAX(l.revision) FROM $localizationCn l 
				WHERE l.revision <= :revision AND l.id = :id")
				->setParameters(array(
					'id' => $localizationId,
					'revision' => $revisionId,
				))
				->getSingleScalarResult();

		// read localization
		$localization = $auditEm->getRepository($localizationCn)
				->find(array('id' => $localizationId, 'revision' => $localizationRevisionId));

		// Oops...
		if ( ! ($localization instanceof Localization)) {
			throw new CmsException(null, 'The restore point is broken and cannot be used anymore.');
		}

		return $localization;
	}

	/**
	 * Get first page localization ID to show in the CMS
	 * @return Entity\Abstraction\Localization
	 */
	protected function getInitialPageLocalization()
	{
		$localeId = $this->getLocale()->getId();
		$localization = null;

		// Try cookie
		if (isset($_COOKIE[self::INITIAL_PAGE_ID_COOKIE])) {
			$pageLocalizationId = $_COOKIE[self::INITIAL_PAGE_ID_COOKIE];
			$localization = $this->entityManager->find(Entity\Abstraction\Localization::CN(), 
					$pageLocalizationId);
		}

		// Root page otherwise
		if (empty($localization)) {
			$page = null;
			$pageDao = $this->entityManager->getRepository(Page::CN());
			/* @var $pageDao PageRepository */
			$pages = $pageDao->getRootNodes();

			if (isset($pages[0])) {
				$page = $pages[0];
			}

			if ($page instanceof Entity\Abstraction\AbstractPage) {
				$localization = $page->getLocalization($localeId);
			}
		}

		if (empty($localization)) {
			return null;
		}

		return $localization;
	}

	/**
	 * Sets initial page ID to show in the CMS
	 * @param string $pageId
	 */
	protected function setInitialPageId($pageId)
	{
		$cookie = new Cookie(self::INITIAL_PAGE_ID_COOKIE, $pageId);
		$cookie->setExpire('+1 month');
		$_COOKIE[self::INITIAL_PAGE_ID_COOKIE] = $pageId;

		$this->getResponse()->setCookie($cookie);
	}

	/**
	 * 
	 * @param Entity\Abstraction\Localization $pageData
	 */
	protected function outputPage(Entity\Abstraction\Localization $pageData)
	{
		$data = null;

		$data = $this->loadNodeMainData($pageData);

		// Add missing parent page data ID
		$parentData = $pageData->getParent();
		$parentDataId = null;

		if ( ! is_null($parentData)) {
			$parentDataId = $parentData->getId();
		}

		$data['parent_id'] = $parentDataId;

		$this->getResponse()->setResponseData($data);
	}

	/**
	 * Loads main node data array
	 * @param Entity\Abstraction\Localization $data
	 * @param boolean $localizationExists will pass page ID not localization ID if not exists
	 * @return array
	 */
	protected function loadNodeMainData(Entity\Abstraction\Localization $data, $localizationExists = true)
	{
		$page = $data->getMaster();
		$locale = $data->getLocale();
		$id = $data->getId();

		$publicEm = ObjectRepository::getEntityManager('#public');

		if ( ! $localizationExists) {
			$id = $page->getId();
		}

		// Main data
		$array = array(
			'id' => $id,
			'master_id' => $page->getId(),
			'title' => $data->getTitle(),
			// TODO: hardcoded
			'icon' => $page instanceof Entity\TemporaryGroupPage ? 'folder' :
					($data instanceof Entity\GroupLocalization ? 'group' :
							($page->getLevel() === 0 ? 'home' : 'page')),
			'preview' => '/cms/lib/supra/img/sitemap/preview/' . ($data instanceof Entity\GroupLocalization ? 'group.png' : 'blank.jpg'),
			'global' => ( ! $page->isRoot() ? $page->getGlobal() : true ),
			'localized' => $localizationExists,
			'editable' => $localizationExists,
			'isDraggable' => $localizationExists,
			'isDropTarget' => true,
		);

		// Allow dropping before/after not created localizations
		if ( ! $localizationExists) {
			$array['droppablePlaces'] = array(
				'before' => true,
				'after' => true,
				'inside' => false,
			);
		}

		// Template ID
		if ($data instanceof Entity\PageLocalization) {
			$template = $data->getTemplate();
			$templateId = null;

			if ( ! empty($template)) {
				$templateId = $template->getId();
			}

			$array['template'] = $templateId;

			$scheduleTime = $data->getScheduleTime();
			if ( ! is_null($scheduleTime)) {
				$array['scheduled'] = true;
			}

			$localizationCount = $page->getLocalizations()->count();
			$array['localization_count'] = $localizationCount;

			$array['full_path'] = $data->getPath()
					->getFullPath(Path::FORMAT_BOTH_DELIMITERS);

			if (is_null($array['full_path'])) {
				$array['full_path'] = '';
			}

			$array['date'] = $data->getCreationTime()->format('Y-m-d');

			$redirect = $this->getPageController()->getRedirectData($data);

			$array['redirect'] = (!empty($redirect['redirect'])) ? $redirect['redirect'] : false;
			$array['redirect_page_id'] = (!empty($redirect['redirect_page_id'])) ? $redirect['redirect_page_id'] : '';
		}

		// Node type
		$type = Entity\Abstraction\Entity::PAGE_DISCR;
		if ($data instanceof Entity\GroupLocalization) {
			$type = Entity\Abstraction\Entity::GROUP_DISCR;
		} elseif ($page instanceof Entity\ApplicationPage) {
			$type = Entity\Abstraction\Entity::APPLICATION_DISCR;
			$array['application_id'] = $page->getApplicationId();
			$conf = PageApplicationCollection::getInstance()->getConfiguration($page->getApplicationId());
			$array['new_children_first'] = $conf->newChildrenFirst;
			$array['isDraggable'] = $conf->isDraggable;
			$array['isDropTarget'] = $conf->isDropTarget;
						
			// empty news application contain virtual children
			if ( ! isset($array['children_count'])) {
				$array['children_count'] = 1;
			}
		}
		$array['type'] = $type;

		// Path data
		$pathPart = null;
		$applicationBasePath = new Path('');

		if ($data instanceof Entity\PageLocalization) {
			$pathPart = $data->getPathPart();

			if ( ! $page->isRoot()) {
				$parentPage = $page->getParent();
				$parentLocalization = $parentPage->getLocalization($locale);

				if ( ! is_null($parentLocalization) && $parentPage instanceof Entity\ApplicationPage) {
					$applicationId = $parentPage->getApplicationId();
					$application = PageApplicationCollection::getInstance()
							->createApplication($parentLocalization, $this->entityManager);

					if (empty($application)) {
						throw new CmsException(null, "Application '$applicationId' was not found");
					}

					$applicationBasePath = $application->generatePath($data);
				}
			}
		}

		$array['unpublished_draft'] = true;
		$array['published'] = false;

		$localizationId = $data->getId();

		$publicLocalization = $publicEm->find(Localization::CN(), $localizationId);

		$array['active'] = true;
		if ($publicLocalization instanceof Localization) {
			$array['unpublished_draft'] = false;

			$publicRevision = $publicLocalization->getRevisionId();
			$draftRevision = $data->getRevisionId();
			if ($draftRevision == $publicRevision) {
				$array['published'] = true;
			}

			if ($publicLocalization instanceof Entity\PageLocalization) {
				$array['active'] = $publicLocalization->isActive();
			}
		} else {
			$array['active'] = false;
		}

		// TODO: maybe should send "null" when path is not allowed? Must fix JS then
		$array['path'] = $pathPart;
		// Additional base path received from application
		$array['basePath'] = $applicationBasePath->getFullPath(Path::FORMAT_RIGHT_DELIMITER);

		return $array;
	}

	/**
	 * Will publish page currently inside pageData property or found by page_id
	 * and locale query parameters
	 */
	protected function publish()
	{
		$publicEm = $this->getPublicEntityManager();

		$pageRequest = $this->getPageRequest();

		$copyContent = function() use ($pageRequest) {
					$pageRequest->publish();
				};

		$publicEm->transactional($copyContent);

		// If all went well, fire the post-publish event for published page localization.
		$eventArgs = new CmsPagePublishEventArgs($this);
		$eventArgs->user = $this->getUser();
		$eventArgs->localization = $this->getPageLocalization();

		$eventManager = ObjectRepository::getEventManager($this);
		$eventManager->fire(CmsPageEventArgs::postPagePublish, $eventArgs);
	}

	/**
	 * Converts referenced element to JS array
	 * @param ReferencedElement\ReferencedElementAbstract $element
	 * @return array
	 */
	protected function convertReferencedElementToArray(ReferencedElement\ReferencedElementAbstract $element, $includeMeta = true)
	{
		$data = $element->toArray();
		$fs = ObjectRepository::getFileStorage($this);
		$em = $fs->getDoctrineEntityManager();

		if ($element instanceof ReferencedElement\LinkReferencedElement) {

			if ($element->getResource() == 'file') {

				$fileId = $element->getFileId();

				if ( ! empty($fileId)) {
					$file = $em->find(File::CN(), $fileId);

					if ($file instanceof File) {
						$fileInfo = $fs->getFileInfo($file);
						$data['file_path'] = $fileInfo['path'];
					}
				}
			}
		} elseif ($element instanceof ReferencedElement\ImageReferencedElement) {

			$imageId = $element->getImageId();

			// ID will be set even if image not found
			$data['image'] = array(
				'id' => $imageId
			);

			if ( ! empty($imageId)) {
				$image = $em->find(Image::CN(), $imageId);

				if ($image instanceof Image) {
					$info = $fs->getFileInfo($image);
					$data['image'] = $info;
				}
			}
			
			// in some cases (gallery) there is no needed additional info
			if ( ! $includeMeta) {
				return array(
					'id' => $imageId,
					'image' => $data['image'],
				);
			}
		}

		return $data;
	}

	/**
	 * Move page at trash 
	 */
	protected function delete()
	{
		$page = $this->getPageLocalization()
				->getMaster();

		$pageId = $page->getId();

		if ($page instanceof Entity\Template) {
			
			$localizationEntity = Entity\PageLocalization::CN();
			
			$dql = "SELECT COUNT(p.id) FROM $localizationEntity p
	                WHERE p.template = ?0";
			
			$count = $this->entityManager->createQuery($dql)
					->setParameters(array($pageId))
					->getSingleScalarResult();

			if ((int) $count > 0) {
				throw new CmsException(null, "Cannot remove template as there are {$count} pages using it.");
			}
			
			$publicEm = ObjectRepository::getEntityManager('#public');
			$count = $publicEm->createQuery($dql)
					->setParameter(0, $pageId)
					->getSingleScalarResult();
			
			if ((int) $count > 0) {
				throw new CmsException(null, "There are {$count} published pages that uses this template! <br/>Un-publish them or publish new version before removing template");
			}
		}

		$pageRequest = $this->getPageRequest();

		$this->entityManager->getEventManager()
				->dispatchEvent(EntityRevisionSetterListener::pagePreDeleteEvent);

		$pageRequest->delete();

		$this->entityManager->getEventManager()
				->dispatchEvent(EntityRevisionSetterListener::pagePostDeleteEvent);

		$eventManager = ObjectRepository::getEventManager($this);

		$eventArgs = new CmsPageDeleteEventArgs($this);
		$eventArgs->localization = $this->getPageLocalization();
		$eventArgs->user = $this->getUser();
		$eventManager->fire(CmsPageEventArgs::postPageDelete, $eventArgs);

		$this->getResponse()
				->setResponseData(true);
	}

	/**
	 * Restoration of page in trash
	 */
	protected function restorePageVersion()
	{
		$this->isPostRequest();

		$auditEm = ObjectRepository::getEntityManager(PageController::SCHEMA_AUDIT);
		$draftEm = $this->entityManager;

		$revisionId = $this->getRequestParameter('revision_id');
		$localizationId = $this->getRequestParameter('page_id');

		// We need it so later we can mark it as restored
		$pageRevisionData = $draftEm->getRepository(PageRevisionData::CN())
				->findOneBy(array('type' => PageRevisionData::TYPE_TRASH, 'id' => $revisionId));

		if ( ! ($pageRevisionData instanceof PageRevisionData)) {
			throw new CmsException(null, 'Page revision data not found');
		}

		$masterId = $auditEm->createQuery("SELECT l.master FROM page:Abstraction\Localization l
				WHERE l.id = :id AND l.revision = :revision")
				->execute(
						array('id' => $localizationId, 'revision' => $revisionId), 
						ColumnHydrator::HYDRATOR_ID);

		$page = null;

		try {
			$page = $auditEm->getRepository(AbstractPage::CN())
					->findOneBy(array('id' => $masterId, 'revision' => $revisionId));
		} catch (MissingResourceOnRestore $missingResource) {
			$missingResourceName = $missingResource->getMissingResourceName();
			throw new CmsException(null, "Wasn't able to load the page from the history because linked resource {$missingResourceName} is not available anymore.");
		}

		if (empty($page)) {
			throw new CmsException(null, "Cannot find the page");
		}

		$localeId = $this->getLocale()->getId();
		$media = $this->getMedia();

		$pageLocalization = $page->getLocalization($localeId);

		if (is_null($pageLocalization)) {
			throw new CmsException(null, 'This page has no localization for current locale');
		}

		$request = new HistoryPageRequestEdit($localeId, $media);
		$request->setDoctrineEntityManager($draftEm);
		$request->setPageLocalization($pageLocalization);

		$draftEventManager = $draftEm->getEventManager();
		$draftEventManager->dispatchEvent(AuditEvents::pagePreRestoreEvent);

		$parent = $this->getPageByRequestKey('parent_id');
		$reference = $this->getPageByRequestKey('reference');

		// Did not allowed to restore root page. Ask Aigars for details
//		if (is_null($parent) && $page instanceof Page) {
//			throw new CmsException('sitemap.error.parent_page_not_found');
//		}

		$draftEm->beginTransaction();
		try {
			$request->restorePage();

			// Read from the draft now
			$page = $draftEm->find(AbstractPage::CN(), $page->getId());

			/* @var $page AbstractPage */

			try {
				if ( ! is_null($reference)) {
					$page->moveAsPrevSiblingOf($reference);
				} elseif ( ! is_null($parent)) {
					$parent->addChild($page);
				}
			} catch (DuplicatePagePathException $uniqueException) {

				$this->getConfirmation('{#sitemap.confirmation.duplicate_path#}');

				$localizations = $page->getLocalizations();
				foreach ($localizations as $localization) {
					$pathPart = $localization->getPathPart();

					// some bad solution
					$localization->setPathPart($pathPart . '-' . time());
				}

				if ( ! is_null($reference)) {
					$page->moveAsPrevSiblingOf($reference);
				} elseif ( ! is_null($parent)) {
					$parent->addChild($page);
				}
			}

			$pageRevisionData->setType(PageRevisionData::TYPE_RESTORED);
			$draftEm->flush();

			$localization = $page->getLocalization($localeId);
			$this->pageData = $localization;
			
		} catch (\Exception $e) {
			$draftEm->rollback();
			throw $e;
		}

		$draftEm->commit();

		$draftEventManager->dispatchEvent(AuditEvents::pagePostRestoreEvent);

		$this->getResponse()
				->setResponseData(true);
	}

	/**
	 * Restores history version of the page localization
	 */
	protected function restoreLocalizationVersion()
	{
		$localizationId = $this->getRequestParameter('page_id');
		$revisionId = $this->getRequestParameter('version_id');

		$localization = $this->findLocalizationInAudit($localizationId, $revisionId);

		$draftEntityManager = $this->entityManager;

		$localeId = $this->getLocale()->getId();
		$media = $this->getMedia();

		$request = new HistoryPageRequestEdit($localeId, $media);
		$request->setPageLocalization($localization);
		$request->setDoctrineEntityManager($draftEntityManager);

		// Call main localization restore routine
		$restoreLocalization = function() use ($request) {
					$request->restoreLocalization();
				};

		$this->entityManager
				->transactional($restoreLocalization);

		// Trigger appropriate event. Will create full restore point.
		$pageEventArgs = new PageEventArgs($draftEntityManager);
		$pageEventArgs->setProperty('referenceId', $localizationId);

		$draftEntityManager->getEventManager()
				->dispatchEvent(AuditEvents::localizationPostRestoreEvent, $pageEventArgs);
	}

	/**
	 * Checks, weither the page is locked by current user or not,
	 * will throw an exception if no, and update lock modified time if yes
	 * @throws ObjectLockedException if page is locked by another user
	 */
	protected function checkLock($createLockOnMiss = true)
	{
		$this->isPostRequest();

		$userId = $this->getUser()->getId();
		$pageData = $this->getPageLocalization();

		$pageLock = $pageData->getLock();

		if ($pageLock instanceof Entity\LockData) {
			if (($pageLock->getUserId() != $userId)) {
				throw new ObjectLockedException('page.error.page_locked', 'Page is locked by another user');
			} else {
				$pageLock->setModificationTime(new \DateTime('now'));
				$this->entityManager->flush();
			}
		} elseif ($createLockOnMiss) {
			// Creates lock if doesn't exist
			$this->createLock($pageData, $userId);
		}
	}

	/**
	 * Removes page lock if exists
	 */
	protected function unlockPage()
	{
		$this->isPostRequest();

		$pageData = $this->getPageLocalization();

		$pageLock = $pageData->getLock();

		if ($pageLock instanceof Entity\LockData) {
			$this->entityManager->remove($pageLock);
			$pageData->setLock(null);

			$this->entityManager->flush();

			$previousRevision = $pageLock->getPageRevision();
			$currentRevision = $pageData->getRevisionId();

			if ($previousRevision != $currentRevision) {
				$this->writeAuditLog("Draft for %item% saved", $pageData);
			}
		}
	}

	/**
	 * Sets page lock, if no lock is found, or if "force"-locking is used;
	 * will output current lock data if page locked by another user and 
	 * force action is not allowed or not provided
	 */
	protected function lockPage()
	{
		$this->isPostRequest();

		$userId = $this->getUser()->getId();
		$pageData = $this->getPageLocalization();

		$allowForced = true; // TODO: hardcoded, should be based on current User rights/auth
		$force = (bool) $this->getRequestParameter('force');

		try {
			$this->checkLock(false);
		} catch (ObjectLockedException $e) {
			if ( ! $force || ! $allowForced) {

				$pageLock = $pageData->getLock();
				$lockedBy = $pageLock->getUserId();

				$userProvider = ObjectRepository::getUserProvider($this);
				$lockOwner = $userProvider->findUserById($lockedBy);

				// If not found will show use ID
				$userName = '#' . $lockedBy;

				if ($lockOwner instanceof User) {
					$userName = $lockOwner->getName();
				}

				$response = array(
					'username' => $userName,
					'datetime' => $pageLock->getCreationTime()->format('c'),
					'allow_unlock' => $allowForced,
				);

				$this->getResponse()
						->setResponseData($response);
				return;
			}
		}

		$this->createLock($pageData, $userId);

		$this->getResponse()->setResponseData(true);
	}

	/**
	 * Creates the lock inside the database
	 * @param Entity\Abstraction\Localization $pageData
	 * @param string $userId
	 */
	protected function createLock(Entity\Abstraction\Localization $pageData, $userId)
	{
		$pageLock = new Entity\LockData();
		$this->entityManager->persist($pageLock);

		$pageLock->setUserId($userId);
		$revisionId = $pageData->getRevisionId();
		$pageLock->setPageRevision($revisionId);
		$pageData->setLock($pageLock);
		$this->entityManager->flush();
	}

	/**
	 * Duplicate the page
	 */
	protected function duplicate(Localization $pageLocalization)
	{
		$request = $this->getPageRequest($pageLocalization);
		$em = $this->entityManager;

		$page = $pageLocalization->getMaster();

		if ($page instanceof Page && $page->isRoot()) {
			throw new CmsException(null, 'Not allowed to duplicate the root page');
		}

		$clonePage = function() use ($request, $em, $page) {
					/* @var $request PageRequestEdit */
					/* @var $em EntityManager */
					/* @var $page AbstractPage */
					/* @var $pageLocalization Localization */

					$em->getEventManager()
							->dispatchEvent(AuditEvents::pagePreDuplicateEvent);

					$newPage = $request->recursiveClone($page, null, true);

					$eventArgs = new LifecycleEventArgs($newPage, $em);
					$em->getEventManager()
							->dispatchEvent(PagePathGenerator::postPageClone, $eventArgs);

					// page indexes in sitemap tree
					$newPage->setLeftValue(0);
					$newPage->setRightValue(0);
					$newPage->setLevel(1);

					$em->getRepository(AbstractPage::CN())
							->getNestedSetRepository()
							->add($newPage);

					$newPage->moveAsNextSiblingOf($page);

					$eventArgs = new PageEventArgs();
					$eventArgs->setEntityManager($em);

					$localizations = $newPage->getLocalizations();

					foreach ($localizations as $newLocalization) {
						$eventArgs->setProperty('referenceId', $newLocalization->getId());

						$em->getEventManager()
								->dispatchEvent(AuditEvents::pagePostDuplicateEvent, $eventArgs);
					}

					return $newPage;
				};

		$newPage = $em->transactional($clonePage);

		$newLocalizations = $newPage->getLocalizations();
		foreach ($newLocalizations as $newLocalization) {
			if ($newLocalization instanceof Entity\TemplateLocalization) {
				$this->pageData = $newLocalization;
				$this->publish();
			}
		}

		$currentLocale = $this->getLocale()
				->getId();

		$response = $this->convertPageToArray($newPage, $currentLocale);

		$this->getResponse()
				->setResponseData($response);
	}

	protected function createLocalization()
	{
		$master = $this->getPage();
		if (is_null($master)) {
			$pageId = $this->getRequestParameter('page_id');
			throw new CmsException('sitemap.error.page_not_found', "Page [{$pageId}] not found");
		}

		$this->checkActionPermission($master, Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE);

		$targetLocale = $this->getRequestParameter('locale');
		$sourceLocale = $this->getRequestParameter('source_locale');

		$localeManager = ObjectRepository::getLocaleManager($this);
		$localeManager->exists($targetLocale);
		$localeManager->exists($sourceLocale);

		if ($targetLocale == $sourceLocale) {
			throw new CmsException(null, 'Page duplicate will do nothing as source locale and target locale are identical');
		}
		
		$sourceLocalization = $master->getLocalization($sourceLocale);
		if (is_null($sourceLocalization)) {
			throw new CmsException(null, "No source localization [{$sourceLocale}] was found");
		}

		// dissalow to create more than one instance of root page
		if ($master instanceof Page && $master->isRoot()) {
			
			$pathEntityName = Entity\PageLocalizationPath::CN();
			
			$dql = "SELECT p FROM $pathEntityName p
				WHERE p.path = :path
				AND p.locale = :locale";
		
			$query = $this->entityManager
					->createQuery($dql)
					->setParameters(array('path' => '', 'locale' => $targetLocale));

			$path = $query->getOneOrNullResult();
			if ($path instanceof Entity\PageLocalizationPath) {
				throw new CmsException(null, 'It is not allowed to create multiple root pages');
			}
		}
		
		
		
		if ($sourceLocalization instanceof Entity\PageLocalization) {

			$template = $sourceLocalization->getTemplate();
			$templateLocalization = $template->getLocalization($targetLocale);
			if (is_null($templateLocalization)) {
				throw new CmsException(null, "There is no localized [{$targetLocale}] version of template");
			}
		}

		$input = $this->getRequestInput();
		$em = $this->entityManager;
		$this->pageData = $sourceLocalization;
		$request = $this->getPageRequest();

		$createLocalization = function() use ($request, $em, $sourceLocalization, $targetLocale, $sourceLocale, $input) {

					$targetLocalization = $request->recursiveClone($sourceLocalization, null, true);

					$targetLocalization->setLocale($targetLocale);

					if ($targetLocalization instanceof Entity\PageLocalization) {

						$template = $targetLocalization->getTemplate();
						$targetTemplateLocalization = $template->getLocalization($targetLocale);
						$targetPlaceHolders = $targetTemplateLocalization->getPlaceHolders();

						$replacedProperties = array();

						foreach ($targetPlaceHolders as $targetPlaceHolder) {

							$name = $targetPlaceHolder->getName();

							if ( ! $targetPlaceHolder->getLocked()) {

								$knownPlaceHolders = $targetLocalization->getPlaceHolders();
								if ( ! $knownPlaceHolders->offsetExists($name)) {
									$targetPlaceHolder = Entity\Abstraction\PlaceHolder::factory($targetLocalization, $name, $targetPlaceHolder);
									$targetPlaceHolder->setMaster($targetLocalization);
									$em->persist($targetPlaceHolder);

									$em->flush();
								} else {
									$targetPlaceHolder = $knownPlaceHolders->offsetGet($name);
								}
							}

							$qb = $em->createQueryBuilder();
							$targetBlocks = $qb->select('b')
									->from(Entity\Abstraction\Block::CN(), 'b')
									->where('b.placeHolder = ?0')
									->orderBy('b.position', 'ASC')
									->getQuery()
									->execute(array($targetPlaceHolder->getId()));

							foreach ($targetBlocks as $targetBlock) {

								$componentClass = $targetBlock->getComponentClass();

								$qb = $em->createQueryBuilder();
								$property = $qb->select('p')
										->from(Entity\BlockProperty::CN(), 'p')
										->join('p.block', 'b')
										->join('b.placeHolder', 'ph')
										->where('p.localization = ?0 AND b.componentClass = ?1 AND ph.name = ?2')
										->orderBy('b.position', 'ASC')
										->setMaxResults(1)
										->getQuery()
										->execute(array($targetLocalization->getId(), $componentClass, $targetPlaceHolder->getName()));

								if ( ! empty($property)) {
									$qb = $em->createQueryBuilder();
									$properties = $qb->select('p')
											->from(Entity\BlockProperty::CN(), 'p')
											->join('p.block', 'b')
											->join('b.placeHolder', 'ph')
											->where('p.localization = ?0 AND b.componentClass = ?1 AND ph.name = ?2 AND b.id = ?3')
											->orderBy('b.position', 'ASC')
											->getQuery()
											->execute(array($targetLocalization->getId(), $componentClass, $targetPlaceHolder->getName(), $property[0]->getBlock()->getId()));
								}

								if ( ! empty($properties)) {

									$qb = $em->createQueryBuilder();
									$targetProperties = $qb->select('p')
											->from(Entity\BlockProperty::CN(), 'p')
											->where('p.block = ?0 AND p.localization = ?1')
											->getQuery()
											->execute(array($targetBlock->getId(), $targetLocalization->getId()));

									if (empty($targetProperties)) {
										foreach ($properties as $property) {
											if ( ! in_array($property->getId(), $replacedProperties)) {
												$property->setBlock($targetBlock);
												array_push($replacedProperties, $property->getId());
											}
										}
									}

									foreach ($targetProperties as $targetProperty) {

										foreach ($properties as $property) {
											if ( ! in_array($property->getId(), $replacedProperties) && $targetProperty->getName() == $property->getName()
													&& $targetProperty->getType() == $property->getType()) {
												
												$property->setBlock($targetBlock);
												array_push($replacedProperties, $property->getId());

												if ($property->getId() !== $targetProperty->getId()) {
//													$qb = $em->createQueryBuilder();
//													$qb->delete(Entity\BlockPropertyMetadata::CN(), 'm')
//															->where('m.blockProperty = ?0')
//															->getQuery()->execute(array($targetProperty->getId()));

													$qb = $em->createQueryBuilder();
													$qb->delete(Entity\BlockProperty::CN(), 'p')
															->where('p.id = ?0 AND p.masterMetadataId IS NULL')
															->getQuery()->execute(array($targetProperty->getId()));
												}
											}
										}
									}
									
//									foreach($properties as $property) {
//										$masterMetadataId = $property->getMasterMetadataId();
//										if ( ! is_null($masterMetadataId)) {
//											$originalMetadataEntity = $em->find(Entity\BlockPropertyMetadata::CN(), $masterMetadataId);
//
//											if ( ! is_null($originalMetadataEntity)) {
//												$originalMetaName = $originalMetadataEntity->getName();
//												$originalMetaProperty = $originalMetadataEntity->getBlockProperty();
//												
//												$block = $property->getBlock();
//												
//												$targetProperty = $em->getRepository(Entity\BlockProperty::CN())
//														->findOneBy(array('name' => $originalMetaProperty->getName(), 'block' => $block));
//												
//												if ( ! is_null($targetProperty)) {
//													$metaCollection = $targetProperty->getMetadata();
//													
//													$metaItem = $metaCollection->get($originalMetaName);
//													if ( ! is_null($metaItem)) {
//														$property->setMasterMetadata($metaItem);
//													}
//												}
//											}
//										}
//									}
								}
							}
						}

						$em->flush();
					}

					if ($input->has('title')) {
						$targetLocalization->setTitle($input->get('title'));
					}

					if ($targetLocalization instanceof Entity\PageLocalization) {

						if ($input->has('path')) {
							$targetLocalization->setPathPart($input->get('path'));
						}

						$pathEntity = $targetLocalization->getPathEntity();
						$pathEntity->setLocale($targetLocale);

						$em->flush();

						$eventArgs = new LifecycleEventArgs($targetLocalization, $em);
						$em->getEventManager()
								->dispatchEvent(PagePathGenerator::postPageClone, $eventArgs);
					}

					return $targetLocalization;
				};

		$em->getEventManager()
				->dispatchEvent(AuditEvents::pagePreDuplicateEvent);

		$targetLocalization = $em->transactional($createLocalization);

		$eventArgs = new PageEventArgs($em);
		$eventArgs->setProperty('referenceId', $targetLocalization->getId());

		$em->getEventManager()
				->dispatchEvent(AuditEvents::pagePostDuplicateEvent, $eventArgs);

		$this->getResponse()
				->setResponseData(array('id' => $targetLocalization->getId()));

		$this->writeAuditLog("%item% created from ({$sourceLocale}) locale", $targetLocalization);

		if ($targetLocalization instanceof Entity\TemplateLocalization) {
			$this->pageData = $targetLocalization;
			$this->publish();
		}
	}

	/**
	 * Write to audit log
	 *
	 * @param string $action
	 * @param mixed $data
	 * @param object $item
	 * @param int $level 
	 */
	protected function writeAuditLog($message, $item = null, $level = AuditLogEvent::INFO)
	{
		if ($item instanceof AbstractPage) {
			$localeId = $this->getLocale()->getId();
			$item = $item->getLocalization($localeId);
		}

		parent::writeAuditLog($message, $item, $level);
	}

	/**
	 * Run after page change
	 */
	protected function savePostTrigger()
	{
		$eventArgs = new CmsPageEventArgs();
		$eventArgs->user = $this->getUser();
		$eventArgs->localization = $this->getPageLocalization();

		$eventManager = ObjectRepository::getEventManager($this);
		$eventManager->fire(CmsPageEventArgs::postPageChange, $eventArgs);
	}

	/**
	 * Helper method for the main sitemap action
	 * @param Entity\Abstraction\AbstractPage $page
	 * @param string $locale
	 * @return array
	 */
	protected function convertPageToArray(Entity\Abstraction\AbstractPage $page, $locale)
	{
		/* @var $localization Entity\Abstraction\Localization */
		$localization = null;

		// Must have group localization with ID equal with master because group localizations are not published currently
		if ($page instanceof Entity\GroupPage) {
			$localization = $page->createLocalization($locale);
		} else {
			$localization = $page->getLocalization($locale);
		}

		$array = array();
		$localizationExists = true;

		if (empty($localization)) {

			$localeManager = ObjectRepository::getLocaleManager($this);
			$localizationExists = false;

			// try to get any localization if page is not localized and is global
			if ($page->isGlobal() || ( ! $page->isGlobal() && $page->isRoot())) {

				// TODO: temporary (and ugly also) workaround to fetch oldest localization from all available
				// this, i suppose, will be replaced with dialog window with localization selector
				$localizations = $page->getLocalizations();
				$localization = $localizations->first();

				// Search for the first created localization by it's ID
				foreach ($localizations as $_localization) {
					/* @var $_localization Entity\Abstraction\Localization */
					if (strcmp($_localization->getId(), $localization->getId()) < 0) {
						$localeId = $_localization->getLocale();

						if ($localeManager->exists($localeId, false)) {
							$localization = $_localization;
						}
					}
				}

				// collecting available localizations
				foreach ($localizations as $_localization) {
					$localeId = $_localization->getLocale();

					if ($localeManager->exists($localeId, false)) {

						$data = array('title' => $_localization->getTitle());

						if ($_localization instanceof Entity\PageLocalization) {
							$data['path'] = $_localization->getPathPart();
						}

						$array['localizations'][$_localization->getLocale()] = $data;
					}
				}
			} else {
				//FIXME: maybe need to throw exception here?
				return null;
			}
		}

		$nodeData = $this->loadNodeMainData($localization, $localizationExists);
		if ( ! empty($nodeData)) {
			$array = array_merge($nodeData, $array);
		}

		return $array;
	}

}
