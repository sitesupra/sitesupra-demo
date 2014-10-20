<?php

namespace Supra\Package\Cms\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Supra\Package\Cms\Pages\Application\PageApplicationInterface;
use Supra\Package\Cms\Entity\Abstraction\Entity as AbstractEntity;
use Supra\Package\Cms\Entity;
use Supra\Package\Cms\Entity\Abstraction\AbstractPage;
use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Entity\PageRevisionData;
use Supra\Package\Cms\Entity\Page;
use Supra\Package\Cms\Entity\GroupPage;
use Supra\Package\Cms\Entity\Template;
use Supra\Package\Cms\Entity\ReferencedElement;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\LockData;
use Supra\Package\Cms\Entity\ApplicationPage;
use Supra\Package\Cms\Pages\Exception\ObjectLockedException;
use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Pages\Layout\Theme\ThemeInterface;
use Supra\Package\Cms\Pages\Request\PageRequestEdit;
use Supra\Package\Cms\Editable\Html;
use Supra\Package\Cms\Editable\EditableInterface;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Editable\Transformer\HtmlEditorValueTransformer;
use Supra\Package\Cms\Exception\CmsException;
use Supra\Package\Cms\Pages\BlockController;

use Supra\Uri\Path;

use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Controller\Pages\PageController;
use Doctrine\ORM\EntityManager;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Repository\PageRepository;
use Supra\Http\Cookie;
use Supra\Cms\CmsAction;
use Supra\NestedSet\Node\DoctrineNode;
use Doctrine\ORM\Query;
use Supra\Database\Doctrine\Hydrator\ColumnHydrator;
use Supra\FileStorage\Entity\Image;
use Supra\FileStorage\Entity\File;
use Supra\User\Entity\User;
use Supra\Controller\Pages\Request\HistoryPageRequestEdit;
use Supra\Loader\Loader;
use Supra\AuditLog\AuditLogEvent;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Supra\Controller\Pages\Listener\PagePathGenerator;
use Supra\Controller\Pages\Event\PageEventArgs;
use Supra\Controller\Pages\Event\AuditEvents;
use Supra\Controller\Pages\Listener\EntityRevisionSetterListener;
use Supra\Controller\Pages\Exception\DuplicatePagePathException;
use Supra\Controller\Pages\Event\SetAuditRevisionEventArgs;
use Supra\Controller\Pages\Exception\MissingResourceOnRestore;
//use Supra\RemoteHttp\Request\RemoteHttpRequest;
//use Supra\RemoteHttp\RemoteHttpRequestService;
use Supra\Controller\Pages\Event;

/**
 * Controller containing common methods
 */
abstract class AbstractPagesController extends AbstractCmsController
{
	protected $application = 'content-manager';

	const GOOGLEAPIS_FONTS_URI = 'https://www.googleapis.com/webfonts/v1/webfonts';

	const INITIAL_PAGE_ID_COOKIE = 'cms_content_manager_initial_page_id';
	const PAGE_CONTROLLER_CLASS = 'Supra\Controller\Pages\PageController';

	/**
	 * @var Entity\Abstraction\Localization
	 */
	protected $pageData;

	/**
	 * @var boolean
	 */
	private $lockTransactionOpened = false;

	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	protected function getEntityManager()
	{
		return $this->container
				->getDoctrine()
				->getManager('cms');
	}

	/**
	 * @throws BadRequestException if request method is not POST
	 */
	protected function isPostRequest()
	{
		if (! $this->container->getRequest()
				->isMethod('post')) {
			throw new MethodNotAllowedException(array('post'));
		}
	}

//	/**
//	 * @param \Exception $e
//	 */
//	protected function finalize(\Exception $e = null)
//	{
//		if ($this->lockTransactionOpened
//				&& $this->entityManager->isOpen()
//				&& $this->entityManager->getConnection()->isTransactionActive()) {
//
//			$this->entityManager->commit();
//			$this->lockTransactionOpened = false;
//		}
//
//		parent::finalize($e);
//	}

//	/**
//	 * TODO: must return configurable controller instance (use repository?)
//	 * @return string
//	 */
//	protected function getPageControllerClass()
//	{
//		return 'Supra\Controller\Pages\PageController';
//	}

//	/**
//	 * Get public entity manager
//	 * @return EntityManager
//	 */
//	protected function getPublicEntityManager()
//	{
//		return ObjectRepository::getEntityManager($this->getPageControllerClass());
//	}

//	/**
//	 * Get page controller instance
//	 * @return PageController
//	 */
//	protected function getPageController()
//	{
//		if (is_null($this->pageController)) {
//			$controllerClass = $this->getPageControllerClass();
//			$this->pageController = Loader::getClassInstance($controllerClass, self::PAGE_CONTROLLER_CLASS);
//
//			// Override to use the draft repository objects
//			ObjectRepository::setCallerParent($this->pageController, $this);
//		}
//
//		return $this->pageController;
//	}

//	/**
//	 * @param Localization $pageLocalization
//	 * @return PageRequestEdit
//	 */
//	protected function getPageRequest(Localization $pageLocalization = null)
//	{
//		$controller = $this->getPageController();
//		$media = $this->getMedia();
//		$user = $this->getUser();
//
//		if (is_null($pageLocalization)) {
//			$pageLocalization = $this->getPageLocalization();
//		}
//
//		$request = PageRequestEdit::factory($pageLocalization, $media);
//		$response = $controller->createResponse($request);
//
//		$controller->prepare($request, $response);
//
//		$request->setUser($user);
//
//		return $request;
//	}

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
	 * @TODO: rename to getLocalization();
	 *
	 * @return Localization
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

// @FIXME: audit events
//		$this->entityManager->getEventManager()
//				->dispatchEvent(\Supra\Controller\Pages\Event\AuditEvents::pagePreEditEvent, $pageEventArgs);

		/**
		 * Set the system current locale if differs from 'locale' parameter received.
		 * This is done for BACK button to work after navigating to page with different language.
		 * NB! this change won't be saved in the currrent locale browser cookie storage.
		 */
		$expectedLocaleId = $this->pageData->getLocaleId();

		if ($this->getCurrentLocale()->getId() !== $expectedLocaleId) {
			$this->container->getLocaleManager()
					->setCurrent($expectedLocaleId);
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
	 * Try selecting page localization by request parameter.
	 * 
	 * @param string $key
	 * @return Localization
	 */
	private function searchLocalizationByRequestKey($key)
	{
		$localizationId = $this->getRequestInput()->get($key);

		// Fix for news application filter folders
		if (strpos($localizationId, '_') !== false) {
			$localizationId = strstr($localizationId, '_', true);
		}

		if (empty($localizationId)) {
			return null;
		}

		return $this->getEntityManager()
				->find(Localization::CN(), $localizationId);
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
			$localization = $this->entityManager->find(Entity\Abstraction\Localization::CN(), $pageLocalizationId);
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
	 * Loads main node data array
	 * 
	 * @param Localization $localization
	 * @return array
	 */
	protected function loadNodeMainData(Localization $localization)
	{
		$localeId = $this->getCurrentLocale()
				->getId();
		
		$isCurrentLocaleLocalization = ($localization->getLocaleId() === $localeId);

		$page = $localization->getMaster();

		$nodeData = array(
			'id'			=> $isCurrentLocaleLocalization ? $localization->getId() : $page->getId(),
			'master_id'		=> $page->getId(),

			'type'			=> $page::DISCRIMINATOR,
			
			'title'			=> $localization->getTitle(),

			'global'		=> $page->isGlobal(),
			'localized'		=> $isCurrentLocaleLocalization,

			'editable'		=> $isCurrentLocaleLocalization,
			'isDraggable'	=> $isCurrentLocaleLocalization,
			'isDropTarget'	=> true,

			// @TODO: previews
			'preview' => null,

			'droppablePlaces' => array(
				'before'	=> true,
				'after'		=> true,
				'inside'	=> $isCurrentLocaleLocalization,
			),

			// @FIXME: icon
			'icon' => null,
		);

		if ($localization instanceof PageLocalization) {
			$nodeData = array_merge($nodeData, $this->getPageLocalizationData($localization));
		}

		return $nodeData;
	}

//	/**
//	 * Will publish page currently inside pageData property or found by page_id
//	 * and locale query parameters
//	 */
//	protected function publish()
//	{
//		$publicEm = $this->getPublicEntityManager();
//
//		$pageRequest = $this->getPageRequest();
//
//		$copyContent = function() use ($pageRequest) {
//					$pageRequest->publish();
//				};
//
//		$publicEm->transactional($copyContent);
//
//		$this->triggerPageCmsEvent(Event\PageCmsEvents::pagePostPublish);
//	}

	/**
	 * Page delete action
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

		// EVENTS:
		// 1. Sets the revision setter listener and audit listener into specific state
		$this->entityManager->getEventManager()
				->dispatchEvent(EntityRevisionSetterListener::pagePreDeleteEvent);

		// 2. Supra's event manager listeners
		$this->triggerPageCmsEvent(Event\PageCmsEvents::pagePreRemove);

		// page remove action
		$pageRequest = $this->getPageRequest();
		$pageRequest->delete();

		// 3. Resets audit/revision listeners back to normal state
		$this->entityManager->getEventManager()
				->dispatchEvent(EntityRevisionSetterListener::pagePostDeleteEvent);

		// 4. Again, the Supra's event manager listeners
		$this->triggerPageCmsEvent(Event\PageCmsEvents::pagePostRemove);

		// Respond with success
		$this->getResponse()->setResponseData(true);
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
				array('id' => $localizationId, 'revision' => $revisionId), ColumnHydrator::HYDRATOR_ID);

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
		$this->checkLock();

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
	protected function checkLock($createOnMiss = true)
	{
		$this->isPostRequest();

		$user = $this->getCurrentUser();

		if (! $user) {
			return null;
		}

		$localization = $this->getPageLocalization();

		if ($localization->isLocked()) {

			$lock = $localization->getLock();
			
			if ($lock->getUserName() !== $user->getUsername()) {
				throw new ObjectLockedException('page.error.page_locked', 'Page is locked by another user');
			} else {

				$entityManager = $this->getEntityManager();

//				if ( ! $this->lockTransactionOpened) {
//					$entityManager->beginTransaction();
//
//					$this->lockTransactionOpened = true;
//				}
				
//				$entityManager->lock($lock, \Doctrine\DBAL\LockMode::PESSIMISTIC_READ);

				$lock->setModificationTime(new \DateTime('now'));
				
				$entityManager->flush($lock);
			}
		} elseif ($createOnMiss) {

			$entityManager = $this->getEntityManager();

//			if ( ! $this->lockTransactionOpened) {
//				$entityManager->beginTransaction();
//				$this->lockTransactionOpened = true;
//			}

			// Creates lock if doesn't exist
//			$entityManager->lock(
//					$this->createLock(),
//					\Doctrine\DBAL\LockMode::PESSIMISTIC_READ
//			);
		}
	}

	/**
	 * Removes page lock if exists
	 */
	protected function unlockPage()
	{
		$this->isPostRequest();

		$localization = $this->getPageLocalization();

		if ($localization->isLocked()) {

			$lock = $localization->getLock();

			$entityManager = $this->getEntityManager();

			$localization->setLock(null);
			$entityManager->remove($lock);

			$entityManager->flush();
		}

//		$this->triggerPageCmsEvent(Event\PageCmsEvents::pagePostUnlock);
	}

	/**
	 * Sets page lock, if no lock is found, or if "force"-locking is used;
	 * will output current lock data if page locked by another user and
	 * force action is not allowed or not provided
	 */
	protected function lockPage()
	{
		$this->isPostRequest();

		$currentUser = $this->getCurrentUser();

		if (! $currentUser) {
			return null;
		}

		$localization = $this->getPageLocalization();

		$force = $this->getRequestInput()
				->filter('force', false, false, FILTER_VALIDATE_BOOLEAN);

		try {
			if ($this->checkLock(false) === null) {
				$this->createLock();
			}
		} catch (ObjectLockedException $e) {
			if ( ! $force) {
				return new SupraJsonResponse($this->getLocalizationLockData($localization));
			}
		}

		return new SupraJsonResponse();
	}

	/**
	 * Creates localization editing lock object.
	 */
	protected function createLock()
	{
		$user = $this->getCurrentUser();

		if (! $user) {
			throw new \LogicException('There is no user to attach the lock.');
		}
		
		$localization = $this->getPageLocalization();

		$lock = new LockData($user, $localization);

		$entityManager = $this->getEntityManager();

		$entityManager->persist($lock);
		$entityManager->flush();

		return $lock;
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

					$newPage = $request->recursiveClone($page);

					$eventArgs = new LifecycleEventArgs($newPage, $em);
					$em->getEventManager()
							->dispatchEvent(PagePathGenerator::postPageClone, $eventArgs);

					// not needed in fact..
//					// page indexes in sitemap tree
//					$newPage->setLeftValue(0);
//					$newPage->setRightValue(0);
//					$newPage->setLevel(1);

					if ($newPage instanceof Template) {
						$repositoryCn = Template::CN();
					} else {
						$repositoryCn = AbstractPage::CN();
					}

					$em->getRepository($repositoryCn)
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

		// Refresh all data
		$newPageId = $newPage->getId();
		$em->clear();

		$newPageRefreshed = $em->find(AbstractPage::CN(), $newPageId);

		$newLocalizations = $newPageRefreshed->getLocalizations();
		foreach ($newLocalizations as $newLocalization) {
			if ($newLocalization instanceof Entity\TemplateLocalization) {
				$this->pageData = $newLocalization;
				$this->publish();
			}
		}

		$currentLocale = $this->getLocale()
				->getId();

		$response = $this->convertPageToArray($newPageRefreshed, $currentLocale);

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

					$targetLocalization = $request->recursiveClone($sourceLocalization, $targetLocale);

					$targetLocalization->setLocale($targetLocale);

					$request->createMissingPlaceHolders();

					// TODO: clone missing placeholders from the template. Even for templates it makes sense to do this.
					// Still – I think it is happening right now on first load.
//					if ($targetLocalization instanceof Entity\PageLocalization) {
//
//						$template = $targetLocalization->getTemplate();
//						$targetTemplateLocalization = $template->getLocalization($targetLocale);
//						$targetPlaceHolders = $targetTemplateLocalization->getPlaceHolders();
//
//						$replacedProperties = array();
//
//						foreach ($targetPlaceHolders as $targetPlaceHolder) {
//
//							$name = $targetPlaceHolder->getName();
//
//							if ( ! $targetPlaceHolder->getLocked()) {
//
//								$knownPlaceHolders = $targetLocalization->getPlaceHolders();
//								if ( ! $knownPlaceHolders->offsetExists($name)) {
//									$targetPlaceHolder = Entity\Abstraction\PlaceHolder::factory($targetLocalization, $name, $targetPlaceHolder);
//									$targetPlaceHolder->setMaster($targetLocalization);
//									$em->persist($targetPlaceHolder);
//
//									$em->flush();
//								} else {
//									$targetPlaceHolder = $knownPlaceHolders->offsetGet($name);
//								}
//							}
//
//							$qb = $em->createQueryBuilder();
//							$targetBlocks = $qb->select('b')
//									->from(Entity\Abstraction\Block::CN(), 'b')
//									->where('b.placeHolder = ?0')
//									->orderBy('b.position', 'ASC')
//									->getQuery()
//									->execute(array($targetPlaceHolder->getId()));
//
//							foreach ($targetBlocks as $targetBlock) {
//
//								$componentClass = $targetBlock->getComponentClass();
//
//								$qb = $em->createQueryBuilder();
//								$property = $qb->select('p')
//										->from(Entity\BlockProperty::CN(), 'p')
//										->join('p.block', 'b')
//										->join('b.placeHolder', 'ph')
//										->where('p.localization = ?0 AND b.componentClass = ?1 AND ph.name = ?2')
//										->orderBy('b.position', 'ASC')
//										->setMaxResults(1)
//										->getQuery()
//										->execute(array($targetLocalization->getId(), $componentClass, $targetPlaceHolder->getName()));
//
//								if ( ! empty($property)) {
//									$qb = $em->createQueryBuilder();
//									$properties = $qb->select('p')
//											->from(Entity\BlockProperty::CN(), 'p')
//											->join('p.block', 'b')
//											->join('b.placeHolder', 'ph')
//											->where('p.localization = ?0 AND b.componentClass = ?1 AND ph.name = ?2 AND b.id = ?3')
//											->orderBy('b.position', 'ASC')
//											->getQuery()
//											->execute(array($targetLocalization->getId(), $componentClass, $targetPlaceHolder->getName(), $property[0]->getBlock()->getId()));
//								}
//
//								if ( ! empty($properties)) {
//
//									$qb = $em->createQueryBuilder();
//									$targetProperties = $qb->select('p')
//											->from(Entity\BlockProperty::CN(), 'p')
//											->where('p.block = ?0 AND p.localization = ?1')
//											->getQuery()
//											->execute(array($targetBlock->getId(), $targetLocalization->getId()));
//
//									if (empty($targetProperties)) {
//										foreach ($properties as $property) {
//											if ( ! in_array($property->getId(), $replacedProperties)) {
//												$property->setBlock($targetBlock);
//												array_push($replacedProperties, $property->getId());
//											}
//										}
//									}
//
//									foreach ($targetProperties as $targetProperty) {
//
//										foreach ($properties as $property) {
//											if ( ! in_array($property->getId(), $replacedProperties) && $targetProperty->getName() == $property->getName()
//													&& $targetProperty->getType() == $property->getType()) {
//
//												$property->setBlock($targetBlock);
//												array_push($replacedProperties, $property->getId());
//
//												if ($property->getId() !== $targetProperty->getId()) {
////													$qb = $em->createQueryBuilder();
////													$qb->delete(Entity\BlockPropertyMetadata::CN(), 'm')
////															->where('m.blockProperty = ?0')
////															->getQuery()->execute(array($targetProperty->getId()));
//
//													$qb = $em->createQueryBuilder();
//													$qb->delete(Entity\BlockProperty::CN(), 'p')
//															->where('p.id = ?0 AND p.masterMetadataId IS NULL')
//															->getQuery()->execute(array($targetProperty->getId()));
//												}
//											}
//										}
//									}
//
////									foreach($properties as $property) {
////										$masterMetadataId = $property->getMasterMetadataId();
////										if ( ! is_null($masterMetadataId)) {
////											$originalMetadataEntity = $em->find(Entity\BlockPropertyMetadata::CN(), $masterMetadataId);
////
////											if ( ! is_null($originalMetadataEntity)) {
////												$originalMetaName = $originalMetadataEntity->getName();
////												$originalMetaProperty = $originalMetadataEntity->getBlockProperty();
////
////												$block = $property->getBlock();
////
////												$targetProperty = $em->getRepository(Entity\BlockProperty::CN())
////														->findOneBy(array('name' => $originalMetaProperty->getName(), 'block' => $block));
////
////												if ( ! is_null($targetProperty)) {
////													$metaCollection = $targetProperty->getMetadata();
////
////													$metaItem = $metaCollection->get($originalMetaName);
////													if ( ! is_null($metaItem)) {
////														$property->setMasterMetadata($metaItem);
////													}
////												}
////											}
////										}
////									}
//								}
//							}
//						}
//
//						$em->flush();
//					}

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
		$this->triggerPageCmsEvent(Event\PageCmsEvents::pageContentPostSave);
	}

	/**
	 * @param AbstractPage $page
	 * @param string $locale
	 * @return array
	 */
	protected function convertPageToArray(AbstractPage $page, $locale)
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

			$localeManager = $this->container->getLocaleManager();
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

		$nodeData = null;
		if ($localization instanceof Localization) {
			$nodeData = $this->loadNodeMainData($localization, $localizationExists);
		}

		if ( ! empty($nodeData)) {
			$array = array_merge($nodeData, $array);
		}

		if ($page instanceof Entity\Template) {
			$array['type'] = 'template';
		}

		return $array;
	}

	/**
	 * Locks the nested set (both for now)
	 */
	protected function lockNestedSet()
	{
		$this->getEntityManager()
				->getRepository(AbstractPage::CN())
				->getNestedSetRepository()
				->lock();
	}

	/**
	 * Unlocks the nested set
	 */
	protected function unlockNestedSet()
	{
		$this->getEntityManager()
				->getRepository(AbstractPage::CN())
				->getNestedSetRepository()
				->unlock();
	}

//	/**
//	 */
//	protected function getGoogleCssFontList()
//	{
//		throw new \Exception("Don't use me bro.");
//
//		$ini = ObjectRepository::getIniConfigurationLoader($this);
//		$apiKey = $ini->getValue('google_fonts', 'api_key', null);
//
//		if ($apiKey === null) {
//			\Log::info("Google Fonts service API key is not configured, skipping");
//			return array();
//		}
//
//		$cache = ObjectRepository::getCacheAdapter($this);
//
//		$fontList = $cache->fetch(__CLASS__);
//
//		if ($fontList === false) {
//
//			// @TODO: move service object to ObjectRepository
//			$service = new RemoteHttpRequestService();
//
//			$request = new RemoteHttpRequest(self::GOOGLEAPIS_FONTS_URI, RemoteHttpRequest::TYPE_GET,
//					array(
//						'key' => $apiKey,
//						'sort' => 'popularity',
//					));
//
//			\Log::info("Requesting Google Fonts API");
//
//			$response = $service->makeRequest($request);
//
//			$responseCode = $response->getCode();
//
//			if ($responseCode !== \Supra\Response\HttpResponse::STATUS_OK) {
//				throw new \RuntimeException("Request to Google Fonts API failed, error code {$responseCode}");
//			}
//
//			$list = json_decode($response->getBody(), true);
//
//			if ($list === false) {
//				throw new \RuntimeException("Failed to decode Google Fonts API response");
//			}
//
//			if (empty($list) || ! isset($list['items'])) {
//				throw new \RuntimeException("Received Google Fonts API response is invalid");
//			}
//
//			// collecting only font families, other data isn't required
//			$fontList = array();
//
//			foreach ($list['items'] as $fontData) {
//				if ( ! isset($fontData['family']) || empty($fontData['family'])) {
//					\Log::warn("Missing font family property in array", $fontData);
//				}
//
//				$fontList[] = $fontData['family'];
//			}
//
//			$cacheTime = $ini->getValue('google_fonts', 'cache_time', 86400);
//			$cache->save(__CLASS__, $fontList, $cacheTime);
//
//		}
//
//		return $fontList;
//	}

	/**
	 * @param string $eventName
	 */
	protected function triggerPageCmsEvent($eventName)
	{
		$eventDispatcher = $this->container->getEventDispatcher();

// @FIXME
		return null;

		$eventArgs = new Event\PageCmsEventArgs();

		$eventArgs->localization = $this->getPageLocalization();
		$eventArgs->user = $this->getCurrentUser();

		$eventDispatcher->dispatch($eventName, $eventArgs);
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\ParameterBag
	 */
	protected function getRequestInput()
	{
		$request = $this->container->getRequest();
		
		return $request->isMethod('POST')
				? $request->request
				: $request->query;
	}
	
	/**
	 * @return Supra\Package\Cms\Pages\Application\PageApplicationManager
	 */
	protected function getPageApplicationManager()
	{
		return $this->container['cms.pages.page_application_manager'];
	}

	/**
	 * @return Supra\Package\Cms\Pages\Layout\Theme\ThemeProviderInterface
	 */
	protected function getThemeProvider()
	{
		return $this->container['cms.pages.theme.provider'];
	}

	/**
	 * @return Supra\Package\Cms\Controller\PageController
	 */
	protected function getPageController()
	{
		return $this->container['cms.pages.controller'];
	}


	/**
	 * @return \Supra\Package\Cms\Pages\Block\BlockCollection
	 */
	protected function getBlockCollection()
	{
		return $this->container['cms.pages.blocks.collection'];
	}

	/**
	 * @return ThemeInterface
	 */
	protected function getActiveTheme()
	{
		return $this->getThemeProvider()
				->getActiveTheme();
	}

	/**
	 * @param Localization $localization
	 * @return array | null
	 */
	protected function getLocalizationLockData(Localization $localization)
	{
		if (! $localization->isLocked()) {
			return null;
		}
		
		$lock = $localization->getLock();

		return array(
			// @FIXME: should not return both, userlogin and username
			'userlogin' => $lock->getUserName(),
			'username'	=> $lock->getUserName(),
			'datetime'	=> $lock->getCreationTime()->format('c'),
			// @TODO: hardcoded
			'allow_unlock' => true,
		);
	}

	/**
	 * Gets page application configuration array for UI
	 *
	 * @param PageApplicationInterface $application
	 * @return array
	 */
	protected function getPageApplicationData(PageApplicationInterface $application)
	{
		return array(
			'id'		=> $application->getId(),
			'title'		=> $application->getTitle(),
			'icon'		=> $application->getIcon(),
			'isDropTarget' => $application->getAllowChildren(),
			'childInsertPolicy' => $application->getNewChildInsertPolicy(),
		);
	}

	/**
	 * @param PageLocalization $localization
	 * @return array
	 */
	private function getPageLocalizationData(PageLocalization $localization)
	{
		$template = $localization->getTemplate();

		$publishedLocalization = $this->findLocalizationPublishedVersion($localization);

		$isLatestVersionPublished = ($publishedLocalization
				&& $publishedLocalization->getRevision() === $localization->getRevision());

		// main data
		$localizationData = array(
			'template'	=> $template->getId(),

			// is scheduled
			'scheduled' => ($localization->getScheduleTime() !== null),

			// TODO: maybe should send "null" when path is not allowed? Must fix JS then
			'path'		=> $localization->getPathPart(),
			'full_path'	=> (string) $localization->getFullPath(),

			// date created
			'date'		=> $localization->getCreationTime()
					->format('Y-m-d'),

			// is the latest version published or not
			'published' => $isLatestVersionPublished
		);

		// redirect data
		$localizationData = array_merge(
				$localizationData,
				$this->getPageLocalizationRedirectData($localization)
		);

		// additional data for Application Page Localization
		if ($localization instanceof ApplicationLocalization) {
			$localizationData = array_merge(
					$localizationData,
					$this->getApplicationPageLocalizationData($localization)
			);
		}

		$applicationBasePath = new Path('');

//		if ($localization instanceof Entity\PageLocalization) {
//			if ( ! $page->isRoot()) {
//				$parentPage = $page->getParent();
//				$parentLocalization = $parentPage->getLocalization($locale);
//
//				if ( ! is_null($parentLocalization) && $parentPage instanceof Entity\ApplicationPage) {
//
//					$applicationId = $parentPage->getApplicationId();
//					$application = PageApplicationCollection::getInstance()
//							->createApplication($parentLocalization, $this->entityManager);
//
//					if (empty($application)) {
//						throw new CmsException(null, "Application '$applicationId' was not found");
//					}
//
//					$applicationBasePath = $application->generatePath($data);
//				}
//			}
//		}

		$array['unpublished_draft'] = true;
		$array['published'] = false;

		$publicLocalization = null;

		// No public stuff for group/temporary pages
		if ( ! $localization instanceof Entity\GroupLocalization) {
//			$localizationId = $data->getId();
//			// FIXME: causes "N" queries for "N" pages loaded in sitemap. Bad.
//			$publicLocalization = $publicEm->find(Localization::CN(), $localizationId);
		}

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

		// Additional base path received from application
		$array['basePath'] = $applicationBasePath->getFullPath(Path::FORMAT_RIGHT_DELIMITER);



		return $localizationData;

//		$localizationCount = $page->getLocalizations()->count();
//		$array['localization_count'] = $localizationCount;
	}

	/**
	 * @param PageLocalization $localization
	 * @return array
	 */
	private function getPageLocalizationRedirectData(PageLocalization $localization)
	{
		if (! $localization->hasRedirectTarget()) {
			return array();
		}

		$redirectTarget = $localization->getRedirectTarget();

		$targetPage = null;
		if ($redirectTarget instanceof Entity\RedirectTargetPage) {
			$targetPage = $redirectTarget->getTargetPage();
		}

		return array(
			'url' => $redirectTarget->getRedirectUrl(),
			'target_page_id' => $targetPage ? $targetPage->getId() : null,
		);
	}

	/**
	 * @param ApplicationLocalization $localization
	 * @return array
	 */
	private function getApplicationPageLocalizationData(ApplicationLocalization $localization)
	{
		$applicationId = $localization->getMaster()
				->getApplicationId();

		$application = $this->getPageApplicationManager()
				->getApplication($applicationId);

		$applicationData = $this->getPageApplicationData($application);
		$applicationData['application_id'] = $applicationData['id'];

		return $applicationData;
	}

	/**
	 * @param Localization $localization
	 * @return Localization
	 */
	private function findLocalizationPublishedVersion(Localization $localization)
	{
		$entityManager = $this->container->getDoctrine()
				->getManager();

		return $entityManager->find(Localization::CN(), $localization->getId());
	}

	/**
	 * @FIXME: do it someway better.
	 */
	protected function configureEditableValueTransformers(EditableInterface $editable, BlockProperty $property)
	{
		if ($editable instanceof Html) {

			$transformer = new HtmlEditorValueTransformer();
			$transformer->setBlockProperty($property);

			$editable->addEditorValueTransformer($transformer);
		}
	}

	/**
	 * @param null|Localization $localization
	 * @return PageRequestEdit
	 */
	protected function createPageRequest(Localization $localization = null)
	{
		$request = new PageRequestEdit(
				Request::create(''),
				$this->getMedia()
		);

		$request->setContainer($this->container);

		$request->setLocalization(
				$localization ? $localization : $this->getPageLocalization()
		);

		return $request;
	}

	/**
	 * @TODO: Move to page content controller abstraction.
	 *
	 * @param Block $block
	 * @param bool $withResponse
	 * @return array
	 */
	protected function getBlockData(Block $block, $withResponse = false)
	{
		$blockController = $this->getBlockCollection()
				->createController($block);

		$pageController = $this->getPageController();

		$pageRequest = $this->createPageRequest();

		$pageController->prepareBlockController($blockController, $pageRequest);

		$blockData = array(
			'id'			=> $block->getId(),
			'type'			=> $block->getComponentName(),
			'closed'		=> false,//@fixme
			'locked'		=> $block->isLocked(),
			'properties'	=> $this->collectBlockPropertyData($blockController),
			// @TODO: check if this still is used somewhere, remove if not.
			'owner_id'		=> $block->getPlaceHolder()
									->getLocalization()->getId()
		);

		if ($withResponse) {
			$blockController->execute();
			$blockData['html'] = (string) $blockController->getResponse();
		}

		return $blockData;
	}

	/**
	 * @TODO: Move to page content controller abstraction.
	 *
	 * @param BlockController $blockController
	 * @return array
	 */
	private function collectBlockPropertyData(BlockController $blockController)
	{
		$propertyData = array();

		$configuration = $blockController->getConfiguration();

		foreach ($configuration->getProperties() as $propertyConfiguration) {

			// @TODO: do it someway better.

			$name = $propertyConfiguration->getName();

			$editable = $propertyConfiguration->getEditable();

			$property = $blockController->getProperty($name);

			$this->configureEditableValueTransformers($editable, $property);

			$editable->setRawValue($property->getValue());

			$propertyData[$name] = array(
				'value' => 	$editable->getEditorValue(),
			);
		}

		return $propertyData;
	}
}
