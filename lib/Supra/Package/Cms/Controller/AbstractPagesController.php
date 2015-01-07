<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\Cache\DoctrineCacheWrapper;
use Supra\Package\Cms\Entity\EditLock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\NestedSet\Node\EntityNodeInterface;
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
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\ApplicationLocalization;
use Supra\Package\Cms\Pages\Exception\ObjectLockedException;
use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Pages\Layout\Theme\ThemeInterface;
use Supra\Package\Cms\Pages\Request\PageRequestEdit;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Exception\CmsException;
use Supra\Package\Cms\Pages\BlockController;
use Supra\Package\Cms\Uri\Path;
use Supra\Package\Cms\Pages\Editable\Transformer;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;


/**
 * Controller containing common methods
 */
abstract class AbstractPagesController extends AbstractCmsController
{
	protected $application = 'content-manager';

//	const GOOGLEAPIS_FONTS_URI = 'https://www.googleapis.com/webfonts/v1/webfonts';

	const INITIAL_PAGE_ID_COOKIE = 'cms_content_manager_initial_page_id';

	/**
	 * @var Localization
	 */
	protected $pageData;

//	/**
//	 * @var boolean
//	 */
//	private $lockTransactionOpened = false;

	/**
	 * @var \SimpleThings\EntityAudit\AuditReader
	 */
	private $auditReader;

	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	protected function getEntityManager()
	{
		return $this->container
				->getDoctrine()
				->getManager();
	}

	/**
	 * @return \SimpleThings\EntityAudit\AuditReader
	 */
	protected function getAuditReader()
	{
		if ($this->auditReader === null) {
			$auditManager = $this->container['entity_audit.manager'];
			/* @var $auditManager \SimpleThings\EntityAudit\AuditManager */
			$this->auditReader = $auditManager->createAuditReader($this->getEntityManager());
			/* @var $auditReader \SimpleThings\EntityAudit\AuditReader */
		}
		
		return $this->auditReader;
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

// @FIXME implement as post-request listener.
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

	/**
	 * @TODO: hardcoded now
	 * @return string
	 */
	protected function getMedia()
	{
		return 'screen';
	}

	/**
	 * @return AbstractPage
	 */
	protected function getPage()
	{
		$page = $this->pageData !== null
				? $this->pageData->getMaster()
				: $this->getPageByRequestKey('page_id');

		if (! $page instanceof AbstractPage) {
			throw new CmsException('sitemap.error.page_not_found');
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
	 * Try selecting abstract page by request parameter.
	 * 
	 * @param string $key
	 * @return null|AbstractPage
	 */
	private function searchPageByRequestKey($key)
	{
		$pageId = $this->getRequestParameter($key);

		if (empty($pageId)) {
			return null;
		}

		return $this->getEntityManager()
				->find(AbstractPage::CN(), $pageId);
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

	/**
	 * Page delete action
	 */
	protected function delete()
	{
		$page = $this->getPageLocalization()
				->getMaster();

		$entityManager = $this->getEntityManager();

		if ($page instanceof Entity\Template) {

			$count = (int) $entityManager->createQuery(sprintf('SELECT COUNT(p.id) FROM %s p WHERE p.template = ?0'))
					->setParameters(array($page->getId()))
					->getSingleScalarResult();

			if ($count > 0) {
				throw new CmsException(null, "Cannot remove template, [{$count}] pages still uses it.");
			}
		}

		$entityManager->remove($page);
		$entityManager->flush();

		return new SupraJsonResponse();
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
	}

	/**
	 * Sets page lock, if no lock is found, or if force is used;
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
			if (! $force) {
				return new SupraJsonResponse($this->getLocalizationEditLockData($localization));
			}
		}

		return new SupraJsonResponse();
	}

	/**
	 * Creates localization edit lock object.
	 */
	protected function createLock()
	{
		$user = $this->getCurrentUser();

		if (! $user) {
			throw new \LogicException('There is no user to attach the lock.');
		}
		
		$localization = $this->getPageLocalization();

		$currentRevision = $this->getAuditReader()
				->getCurrentRevision($localization::CN(), $localization->getId());

		$lock = new EditLock($user, $localization, $currentRevision);

		$entityManager = $this->getEntityManager();

		$entityManager->persist($lock);
		$entityManager->flush();

		return $lock;
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
		if ($page instanceof GroupPage) {
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

						if ($localeManager->hasLocale($localeId)) {
							$localization = $_localization;
						}
					}
				}

				// collecting available localizations
				foreach ($localizations as $_localization) {
					$localeId = $_localization->getLocale();

					if ($localeManager->hasLocale($localeId)) {

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
	 * Locks the nested set for entity.
	 *
	 * @param string|EntityNodeInterface $entityNode
	 */
	protected function lockNestedSet($entityNode)
	{
		$class = is_string($entityNode) ? $entityNode : $entityNode->getNestedSetRepositoryClassName();

		$this->getEntityManager()
				->getRepository($class)
				->getNestedSetRepository()
				->lock();
	}

	/**
	 * Unlocks the nested set.
	 *
	 * @param string|EntityNodeInterface $entityNode
	 */
	protected function unlockNestedSet($entityNode)
	{
		$class = is_string($entityNode) ? $entityNode : $entityNode->getNestedSetRepositoryClassName();

		$this->getEntityManager()
				->getRepository($class)
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
	 * @return \Supra\Package\Cms\Pages\PageManager
	 */
	protected function getPageManager()
	{
		return $this->container['cms.pages.page_manager'];
	}

	/**
	 * @return \Supra\Package\Cms\Pages\Layout\Theme\ThemeProviderInterface
	 */
	protected function getThemeProvider()
	{
		return $this->container['cms.pages.theme.provider'];
	}

	/**
	 * @return \Supra\Package\Cms\Controller\PageController
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
	protected function getLocalizationEditLockData(Localization $localization)
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

		$currentRevision = $this->getAuditReader()
				->getCurrentRevision($localization::CN(), $localization->getId());
		
		$publishedLocalization = $this->findLocalizationPublishedVersion($localization);

		// @TODO: add to int cast inside AuditReader.
		$isLatestVersionPublished = $localization->isPublished()
				&& $localization->getPublishedRevision() === (int) $currentRevision;

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

			'active' => $publishedLocalization ? $publishedLocalization->isActive() : $localization->isActive(),

			'published' => $localization->isPublished(),
			// is the latest version published or not
			'published_latest' => $isLatestVersionPublished,
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

//		$applicationBasePath = new Path('');

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

		// No public stuff for group/temporary pages
//		if ( ! $localization instanceof Entity\GroupLocalization) {
//			$localizationId = $data->getId();
//			// FIXME: causes "N" queries for "N" pages loaded in sitemap. Bad.
//			$publicLocalization = $publicEm->find(Localization::CN(), $localizationId);
//		}

		// Additional base path received from application
//		$array['basePath'] = $applicationBasePath->getFullPath(Path::FORMAT_RIGHT_DELIMITER);

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
		if (! $localization->isPublished()) {
			return null;
		}

		return $this->getAuditReader()->find(
				$localization::CN(),
				$localization->getId(),
				$localization->getPublishedRevision()
		);
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

		$pageRequest = $this->createPageRequest();

		$blockController->prepare($pageRequest);

		$blockData = array(
			'id'			=> $block->getId(),
			'type'			=> $blockController->getConfiguration()->getName(),
			'closed'		=> false, //@fixme
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
	 * @return array
	 */
	protected function getActiveThemeLayoutsData()
	{
		$themeProvider = $this->getThemeProvider();

		$theme = $themeProvider->getActiveTheme();

		$layoutsData = array();

		foreach ($theme->getLayouts() as $layout) {

			$layoutName = $layout->getName();

			$layoutsData[] = array(
				'id'	=> $layoutName,
				'title' => $layout->getTitle(),
				'icon'	=> $layout->getIcon(),
			);
		}

		return $layoutsData;
	}

	/**
	 * PagesTemplateController::saveSettingsAction()
	 * and PagesPageController::saveSettingsAction() methods common code.
	 */
	protected function saveLocalizationCommonSettingsAction()
	{
		$localization = $this->getPageLocalization();
		$input = $this->getRequestInput();

		//@TODO: create some simple objects for save post data with future validation implementation?

		$title = trim($input->get('title'));
		if (empty($title)) {
			throw new CmsException(null, 'Title cannot be empty.');
		}

		$localization->setTitle($title);

		$localization->setVisibleInMenu(
				$input->filter('is_visible_in_menu', null, false, FILTER_VALIDATE_BOOLEAN)
		);

		$localization->setVisibleInSitemap(
				$input->filter('is_visible_in_sitemap', null, false, FILTER_VALIDATE_BOOLEAN)
		);

		$localization->setIncludedInSearch(
				$input->filter('include_in_search', null, false, FILTER_VALIDATE_BOOLEAN)
		);

		$localization->setChangeFrequency($input->get('page_change_frequency'));

		$localization->setPagePriority($input->get('page_priority'));
	}

	/**
	 * PagesTemplateController::saveAction(),
	 * PagesGroupController::saveAction()
	 * and PagesPageController::saveAction() methods common code.
	 */
	protected function saveLocalizationCommonAction()
	{
		$localization = $this->getPageLocalization();

		if ($this->getRequestInput()->has('title')) {
			
			$title = trim($this->getRequestParameter('title'));

			if (empty($title)) {
				throw new CmsException(null, 'Title cannot be empty.');
			}

			$localization->setTitle($title);
		}
	}

	/**
	 * @param BlockController $blockController
	 * @return array
	 */
	private function collectBlockPropertyData(BlockController $blockController)
	{
		$propertyData = array();

		$configuration = $blockController->getConfiguration();

		foreach ($configuration->getProperties() as $config) {
			$propertyData[$config->name] = array(
				'value' => $blockController->getPropertyEditorValue(
					$config->name,
					$blockController
				)
			);
		}

		return $propertyData;
	}
}
