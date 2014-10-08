<?php

namespace Supra\Package\Cms\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Supra\Core\Controller\Controller;
use Supra\Package\Cms\Entity\Abstraction\PlaceHolder;
use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Pages\Exception\LayoutNotFound;
use Supra\Package\Cms\Pages\Listener\BlockExecuteListener;
use Supra\Package\Cms\Pages\BlockController;
use Supra\Package\Cms\Pages\Block\BlockCollection;
use Supra\Package\Cms\Pages\Response\PageResponse;
use Supra\Package\Cms\Pages\Response\PlaceHolderResponseView;
use Supra\Package\Cms\Pages\Response\PlaceHolderResponseEdit;
use Supra\Package\Cms\Pages\Layout\Theme\ThemeLayoutInterface;
use Supra\Package\Cms\Pages\Response\ResponseContext;
use Supra\Package\Cms\Pages\Request\PageRequest;
use Supra\Package\Cms\Pages\Request\PageRequestEdit;

use Supra\Controller\Layout;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Uri\Path;
//use Supra\Response\ResponseContext;
use Supra\Response\ResponseContextLocalProxy;
use Supra\Controller\Pages\Event\BlockEvents;
use Supra\Controller\Pages\Event\BlockEventsArgs;
use Supra\Cache\CacheGroupManager;
use Supra\Controller\Exception\AuthorizationRequiredException;
use Supra\Controller\Pages\Response\PlaceHolderGroup;
use Supra\Controller\Exception\StopRequestException;

class PageController extends Controller
{
	const EVENT_POST_PREPARE_CONTENT = 'postPrepareContent';
	const EVENT_BLOCK_START = 'blockStartExecuteEvent';
	const EVENT_BLOCK_END = 'blockEndExecuteEvent';
	const CACHE_GROUP_NAME = 'Supra\Controller\Pages';

	/**
	 * @var PageResponse
	 */
	protected $response;

	/**
	 * @var PageRequest
	 */
	protected $pageRequest;

	/**
	 * List of collected block controllers.
	 * 
	 * @var array
	 */
	private $blockControllers = array();

//	/**
//	 * @var array
//	 */
//	private $placeHolderGroupResponses = array();

	/**
	 * @var array
	 */
	private $blockContentCache = array();

	/**
	 * Keeps info about blocks which result must be cached in the end
	 * @var array
	 */
	private $blockCacheRequests = array();

	/**
	 * @FIXME: obtain the request automatically.
	 *
	 * @param PageRequest $request
	 */
	public function setPageRequest(PageRequest $request)
	{
		$this->pageRequest = $request;
	}

	public function indexAction()
	{
		return $this->execute();
	}

	public function execute()
	{
		$pageRequest = $this->getPageRequest();

		try {
			$localization = $pageRequest->getPageLocalization();

			if (! $localization instanceof PageLocalization) {
				throw new \UnexpectedValueException(sprintf(
						'Expecting PageLocalization object only [%s] received.',
						get_class($localization)
				));
			}

			// Check redirect for public calls
			if ($pageRequest instanceof PageRequestView) {
				if ($localization->hasRedirectTarget()) {

					$this->checkForRedirectLoop($localization);

					$redirectUrl = $localization->getRedirectTarget()
							->getRedirectUrl();

					if (! empty($redirectUrl)) {
						return new RedirectResponse($redirectUrl);
					}

					throw new ResourceNotFoundException;
				}
			}
			
		} catch (ResourceNotFoundException $e) {
			try {
				$this->getLocalizationByPath('404');
			} catch (ResourceNotFoundException $e404) {
				// re-throw original exception if 404 page does not exists.
				throw $e;
			}
		}

		// Continue processing
		$layout = $pageRequest->getLayout();

		if ($layout === null) {
			throw new LayoutNotFound(sprintf(
					'No layout found for page localization [%s]',
					$localization->getId()
			));
		}

		$this->findBlockCache();

		$this->findBlockControllers();

		// Initialize block property set so no additional queries are registered
		// for the first block which does it
		$pageRequest->getBlockPropertySet();

		$this->prepareBlockControllers();

		// The cache might be context dependent
		$this->findContextDependentBlockCache();

		$this->executeBlockControllers();

// @FIXME: move event from current ns
//		$eventArgs = new Event\PostPrepareContentEventArgs($this);
//		$eventArgs->request = $this->getRequest();
//		$eventArgs->response = $this->getResponse();
//
//		$eventManager = ObjectRepository::getEventManager($this);
//		$eventManager->fire(self::EVENT_POST_PREPARE_CONTENT, $eventArgs);

		$response = $this->getPageResponse();

		if (($blockId = $pageRequest->getBlockRequestId()) === null) {

			$placeResponses = $this->getPlaceResponses($pageRequest);
			$this->processLayout($layout, $placeResponses);
			
		} else {
			$this->iterateBlocks(
					function(Block $block, BlockController $blockController) use ($blockId, $response) {

						if ($block->getId() === $blockId || $block->getComponentClass() === $blockId) {
							$response->addResponsePart($blockController->execute());
						}
					},
					BlockExecuteListener::ACTION_RESPONSE_COLLECT
			);
		}

		return $response;
	}
	
	/**
	 * @param Entity\Theme\ThemeLayout $layout
	 * @param array $blocks array of block responses
	 */
	protected function processLayout(ThemeLayoutInterface $layout, array $placeResponses)
	{
		$layoutProcessor = $this->getLayoutProcessor();

		$layoutSrc = $layout->getFilename();

		$layoutProcessor->getPlaces($layoutSrc);

		$response = $this->getPageResponse();

		$layoutProcessor->process($response, $placeResponses, $layoutSrc);
	}

	protected function findBlockCache()
	{
		$request = $this->getPageRequest();

		/* @var $request PageRequest */
		$localization = $request->getPageLocalization();

		$blockContentCache = &$this->blockContentCache;
		$blockCacheRequests = &$this->blockCacheRequests;

		// Don't search for cache in CMS
		if ( ! $request instanceof Request\PageRequestView) {
			return;
		}

		$cache = ObjectRepository::getCacheAdapter($this);
		$cacheGroupManager = new CacheGroupManager();
		$self = $this;

		$cacheSearch = function(Entity\Abstraction\Block $block)
				use ($self, $localization, $cacheGroupManager, $cache, &$blockContentCache, &$blockCacheRequests, $request) {

					$blockClass = $block->getComponentClass();
					$configuration = ObjectRepository::getComponentConfiguration($blockClass);

					if ( ! empty($configuration->cache)) {
						$blockCache = $configuration->cache;
					} else {
						$blockCache = null;
					}

					if ($blockCache instanceof Configuration\BlockControllerCacheConfiguration) {

						$blockId = $block->getId();
						$blockCacheRequests[$blockId] = $blockCache;

						$self->searchResponseCache($blockId, $localization, $block);
					}
				};

		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($cacheSearch, BlockExecuteListener::ACTION_CACHE_SEARCH);
	}

	/**
	 * Create block controllers
	 */
	protected function findBlockControllers()
	{
		$request = $this->getPageRequest();
		
		$blockContentCache = &$this->blockContentCache;

		$blockCollection = $this->getBlockCollection();

		// function which adds controllers for the block
		$controllerFactory = function(Block $block, &$blockController) use ($blockCollection, &$blockContentCache) {

					// Skip controller creation if cache found
					$blockId = $block->getId();
					if (array_key_exists($blockId, $blockContentCache)) {
						$blockController = new CachedBlockController($blockContentCache[$blockId]);

						return;
					}

					$blockController = $blockCollection->createController($block);
				};

		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($controllerFactory, BlockExecuteListener::ACTION_CONTROLLER_SEARCH);
	}

	/**
	 * Prepare block controllers
	 */
	protected function prepareBlockControllers()
	{
		$request = $this->getPageRequest();

		$responseContext = $this->getPageResponse()
				->getContext();

// @FIXME: response context

//		if ( ! $responseContext instanceof ResponseContext) {
//			$responseContext = new ResponseContext();
//
//			$this->getResponse()
//				->setContext($responseContext);
//		}

		$blockContentCache = $this->blockContentCache;
		$blockCacheRequests = $this->blockCacheRequests;

		// function which adds controllers for the block
		$prepare = function(Block $block, BlockController $blockController) use ($request, $responseContext, &$blockContentCache, &$blockCacheRequests) {

					$blockId = $block->getId();

					// Cached response, need to merge local context
					if (array_key_exists($blockId, $blockContentCache)) {
						$cachedResponse = $blockContentCache[$blockId];
						/* @var $cachedResponse Response\HttpResponse */
						$context = $cachedResponse->getContext();
						/* @var $context ResponseContext */

						$context->flushToContext($responseContext);

						return;
					} else {

						// Creates local context proxy if the response will be cached
						if (isset($blockCacheRequests[$blockId])) {
							$responseContext = new ResponseContextLocalProxy($responseContext);
						}

						$block->prepareController($blockController, $request, $responseContext);
					}
				};

		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($prepare, BlockExecuteListener::ACTION_CONTROLLER_PREPARE);
	}

	/**
	 * Late cache check for blocks which cache key depends on response context values
	 */
	protected function findContextDependentBlockCache()
	{
		$request = $this->getPageRequest();

		/* @var $request PageRequest */
		$localization = $request->getPageLocalization();
		$blockContentCache = &$this->blockContentCache;
		$blockCacheRequests = &$this->blockCacheRequests;

		// Don't search for cache in CMS
		if ( ! $request instanceof Request\PageRequestView) {
			return;
		}

		$cacheGroupManager = new CacheGroupManager();
		$response = $this->getResponse();
		$context = $response->getContext();
		$self = $this;

		$cacheSearch = function(Block $block, BlockController &$blockController)
				use ($self, $localization, $cacheGroupManager, $cache, &$blockContentCache, &$blockCacheRequests, $request, $context) {

					$blockId = $block->getId();

					$self->searchResponseCache($blockId, $localization, $block, $context);
				};

		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($cacheSearch, BlockExecuteListener::ACTION_DEPENDENT_CACHE_SEARCH);
	}

	/**
	 * Try searching the response cache
	 * @param string $blockId
	 * @param Entity\Abstraction\Localization $localization
	 * @param Entity\Abstraction\Block $block
	 * @param ResponseContext $context
	 * @return boolean
	 * @private
	 */
	public function searchResponseCache($blockId, Entity\Abstraction\Localization $localization, Entity\Abstraction\Block $block, ResponseContext $context = null)
	{
		if ( ! array_key_exists($blockId, $this->blockCacheRequests)) {
			return false;
		}

		$blockCache = $this->blockCacheRequests[$blockId];
		/* @var $blockCache Configuration\BlockControllerCacheConfiguration */

		$cacheKey = $blockCache->getCacheKey($localization, $block, $context);

		if (is_null($cacheKey)) {
			// Cache disabled, forget the request
			unset($this->blockCacheRequests[$blockId]);
			return false;
		}

		if (empty($cacheKey)) {
			return false;
		}

		$cache = ObjectRepository::getCacheAdapter($this);

		$content = $cache->fetch($cacheKey);
		$responseCache = null;

		if ($content === false) {
			return false;
		}

		$responseCache = unserialize($content);
		/* @var $responseCache Response\HttpResponse */

		if ( ! $responseCache instanceof Response\HttpResponse) {
			return false;
		}

		$changed = $responseCache->hasResourceChanged();

		if ($changed) {
			return false;
		}

		$this->blockContentCache[$blockId] = $responseCache;

		// Cache found, don't need to cache
		unset($this->blockCacheRequests[$blockId]);

		// Don't load properties
		$this->request->skipBlockPropertyLoading($blockId);

		// Rewrite controller instance
		$this->blockControllers[$blockId] = new CachedBlockController($this->blockContentCache[$blockId]);

		$cachedContext = $responseCache->getContext();
		$mainContext = $this->getResponse()->getContext();
		$cachedContext->flushToContext($mainContext);

		return true;
	}

	/**
	 * Execute block controllers
	 */
	protected function executeBlockControllers()
	{
		$blockContentCache = $this->blockContentCache;

		// function which adds controllers for the block
		$prepare = function(Block $block, BlockController $blockController) use (&$blockContentCache) {

					if (array_key_exists($block->getId(), $blockContentCache)) {
						return null;
					}

// @FIXME: the same is already done on controller creation.
//					// It is important to prepare the twig helper for each block controller right before execution
//					$blockController->prepareTwigEnvironment();

					$blockController->execute();
				};

		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($prepare, BlockExecuteListener::ACTION_CONTROLLER_EXECUTE);
	}

	/**
	 * Creates place holder response object.
	 * 
	 * @param PlaceHolder $placeHolder
	 * @return PlaceHolderResponse
	 */
	public function createPlaceResponse(PlaceHolder $placeHolder)
	{
		return $this->getPageRequest() instanceof PageRequestEdit
				? new PlaceHolderResponseEdit($placeHolder)
				: new PlaceHolderResponseView($placeHolder);

//		$group = $placeHolder->getGroup();
//
//		if ( ! empty($group)) {
//
//			$groupName = $group->getName();
//
//			if (isset($this->placeHolderGroupResponses[$groupName])) {
//				$groupResponse = $this->placeHolderGroupResponses[$groupName];
//			} else {
//
//				if ($this->request instanceof namespace\Request\PageRequestEdit) {
//					$groupResponse = new PlaceHolderGroup\PlaceHolderGroupResponseEdit();
//				} else {
//					$groupResponse = new PlaceHolderGroup\PlaceHolderGroupResponseView();
//				}
//
//				$groupResponse->setGroupName($groupName);
//
//				$theme = $this->getRequest()
//						->getLayout()
//						->getTheme();
//
//				$groupLayoutName = $group->getGroupLayout();
//
//				$groupLayouts = $theme->getPlaceholderGroupLayouts();
//
//				if ( $groupLayouts && $groupLayouts->offsetExists($groupLayoutName)) {
//					$groupResponse->setGroupLayout($groupLayouts->get($groupLayoutName));
//				}
//
//				$this->placeHolderGroupResponses[$groupName] = $groupResponse;
//			}
//
//			$groupResponse->addPlaceHolderResponse($response);
//
//			return $groupResponse;
//		}
//
//		return $response;
	}

	/**
	 * Iterates through blocks and returs array of place holder responses
	 * @return array
	 */
	protected function getPlaceResponses()
	{
		$placeResponses = array();
		
		$request = $this->getPageRequest();

		$placeHolders = $request->getPlaceHolderSet();

		$localization = $request->getPageLocalization();

		$finalPlaceHolders = $placeHolders->getFinalPlaceHolders();

		foreach ($finalPlaceHolders as $name => $placeHolder) {
			$placeResponses[$name] = $this->createPlaceResponse($placeHolder);
		}

		$blockCacheRequests = &$this->blockCacheRequests;

		$cache = $this->container->getCache();

		$collectResponses = function(Block $block, BlockController $blockController)

				use (&$placeResponses, $localization, $request, $blockCacheRequests, $cache) {

					$response = $blockController->getResponse();

					$blockId = $block->getId();

					$placeName = $block->getPlaceHolder()
							->getName();

					if ( ! isset($placeResponses[$placeName])) {
						//TODO: what is the action on such case?
						throw new Exception\LogicException("Logic problem – final place holder by name $placeName is not found");
					}

					$placeResponse = $placeResponses[$placeName];

					$placeResponse->output($response);


//					//TODO: move to separate method
//					if ($request instanceof Request\PageRequestEdit) {
//						$blockName = $block->getComponentName();
//
//						if ($blockController instanceof BrokenBlockController) {
//							$blockName = $blockController::BLOCK_NAME;
//						}
//
//						$prefixContent = '<div id="content_' . $blockId
//								. '" class="yui3-content yui3-content-' . $blockName
//								. ' yui3-content-' . $blockName . '-' . $blockId . '">';
//
//						$placeHolderResponse->output($prefixContent);
//					}

//					$placeHolderResponse->output($response);
//
//					if ($request instanceof Request\PageRequestEdit) {
//						$placeHolderResponse->output('</div>');
//					}

					if (isset($blockCacheRequests[$blockId])) {
						$blockCache = $blockCacheRequests[$blockId];
						$context = $response->getContext();
						$cacheKey = $blockCache->getCacheKey($localization, $block, $context);
						$lifetime = $blockCache->getLifetime();

						try {
							$serializedResponse = serialize($response);
							$cache->store($cacheKey, $serializedResponse, $lifetime);
						} catch (\Exception $e) {
							$blockName = $block->getComponentName();
						//	$log->error("Could not serialize response of block $blockName: ", $e->__toString());
						}
					}
				};

		// Iterates through all blocks and collects placeholder responses
		$this->iterateBlocks($collectResponses, BlockExecuteListener::ACTION_RESPONSE_COLLECT);

		return $placeResponses;
	}

	/**
	 * @FIXME
	 */
	public function returnPlaceResponses()
	{
		return $this->getPlaceResponses();
	}

	/**
	 * Iteration funciton for specific array of blocks
	 * @param \Closure $function
	 * @return array
	 */
	protected function iterateBlocks(\Closure $function, $eventAction = null)
	{
		$blocks = $this->getPageRequest()
				->getBlockSet();

		$eventDispatcher = $this->container->getEventDispatcher();

		$return = array();
		$unsetBlocksByIndex = array();

		/* @var $block Entity\Abstraction\Block */
		foreach ($blocks as $index => $block) {

			$blockId = $block->getId();
			/* @var $blockController BlockController */

			if ( ! isset($this->blockControllers[$blockId])) {
				$this->blockControllers[$blockId] = null;
			}

			$blockController = &$this->blockControllers[$blockId];

			try {

// @FIXME: events
				$eventAction = null;

				if ( ! is_null($eventAction)) {
					$eventArgs = new BlockEventsArgs($blockController);
					$eventArgs->block = $block;
					// Assigned by reference because "null" can change to object after closure execution
					$eventArgs->blockController = &$blockController;
					$eventArgs->actionType = $eventAction;
					$eventArgs->request = $this->request;
					$eventArgs->response = $this->response;
					$eventArgs->blockRequest = ($this->getRequest()->getBlockRequestId() !== null);

// @FIXME: eventDispatcher
//					$eventManager->fire(BlockEvents::blockStartExecuteEvent, $eventArgs);

					$blockTimeStart = microtime(true);
				}

				$blockControllerName = $block->getComponentClass();
				if ( ! empty($blockController)) {
					$blockControllerName = get_class($blockController);
				}

				// Should not throw exceptions
//				ObjectRepository::beginControllerContext($blockControllerName);

				// NB! Block controller variable might be rewritten in the function
				$e = null;

				try {
					$return[$index] = $function($block, $blockController);
				} catch (\Exception $e) {
					// exception raised while initializing the controller (e.g. while reading configuration)
					// will throw after closing controller execution context
				}

//				ObjectRepository::endControllerContext($blockControllerName);

				if ($e) {
					throw $e;
				}

				if (
						$blockController instanceof BlockController &&
						$blockController->hadException()
				) {

					$exception = $blockController->hadException();

					if ($exception instanceof StopRequestException) {

						$response = $blockController->getResponse();

						$response->cleanOutput();

						$response->flushToResponse($this->getResponse());

						throw $exception;
					}

					// Don't cache failed blocks
					unset($this->blockCacheRequests[$blockId]);

					// Add exception to blockEndExecute event.
//					$eventArgs->exception = $exception;
				}

				if ( ! is_null($eventAction)) {

					if ( ! is_null($blockController)) {
						$eventArgs->setCaller($blockController);
					}

					$blockTimeEnd = microtime(true);
					$blockExecutionTime = $blockTimeEnd - $blockTimeStart;
					$eventArgs->duration = $blockExecutionTime;
					$eventArgs->cached = isset($this->blockContentCache[$blockId]);
					$eventDispatcher->fire(BlockEvents::blockEndExecuteEvent, $eventArgs);
				}
			} catch (Exception\SilentBlockSkipException $e) {
				$unsetBlocksByIndex[] = $index;
			} catch (Exception\InvalidBlockException $e) {

				\Log::warn("Skipping block $block because of raised SkipBlockException: {$e->getMessage()}");

				$unsetBlocksByIndex[] = $index;
			}
		}

		// unset afterwards, or else some blocks get prepared 2 times..
		foreach ($unsetBlocksByIndex as $index) {
			unset($blocks[$index]);
		}

		return $return;
	}

	protected function getLocalizationByPath($pathString)
	{
		$request = $this->getPageRequest();

		$request->clear();
		
		$request->attributes->set('path', $pathString);

		$request->getPageLocalization();
	}

	private function checkForRedirectLoop(PageLocalization $localization)
	{
		// @FIXME: implement me
		throw new \Exception('Not implemented.');

//		$redirectPageIds = $data = $parentData = array();
//
//		$linkElement = $pageLocalization->getRedirect();
//		$em = $this->getEntityManager();
//
//		$redirect = false;
//		$redirectLocalizationId = null;
//
//		if ( ! $linkElement instanceof LinkReferencedElement) {
//			return array();
//		}
//
//		do {
//			$pageLocalizationId = $pageLocalization->getId();
//
//			// check if localization id is not in loop
//			if (in_array($pageLocalizationId, $redirectPageIds)) {
//
//				$message = "Looks like page (#id: {$pageLocalizationId}, #title: \"{$pageLocalization->getTitle()}\") " .
//						'is linking to another page which already was in redirect chain.';
//
//				\Log::error($message);
//
//				//			$this->writeAuditLog($message);
//				return array();
//			}
//
//
//			$redirectPageIds[] = $pageLocalizationId;
//
//			$redirectPageId = $redirectLocalization = null;
//			$resource = $linkElement->getResource();
//
//			$data = array();
//
//			switch ($resource) {
//				// parse fixed redirect
//				case LinkReferencedElement::RESOURCE_PAGE:
//					// searching for redirect page
//					$redirectPageId = $linkElement->getPageId();
//					if (empty($redirectPageId)) {
//						unset($linkElement);
//						break;
//					}
//
//					$redirectPage = $em->getRepository(Entity\Abstraction\AbstractPage::CN())
//							->findOneById($redirectPageId);
//
//					if ( ! $redirectPage instanceof Entity\Abstraction\AbstractPage) {
//						unset($linkElement);
//						break;
//					}
//
//					// redirect localization
//					$redirectLocalization = $redirectPage->getLocalization($pageLocalization->getLocale());
//
//					if ( ! $redirectLocalization instanceof Entity\PageLocalization) {
//						unset($linkElement);
//						break;
//					}
//
//					$redirect = true;
//					$redirectLocalizationId = $redirectLocalization->getId();
//					$path = '/' . $redirectLocalization->getLocale()
//							. $redirectLocalization->getFullPath(Path::FORMAT_BOTH_DELIMITERS);
//
//					$data = array(
//						'redirect' => $redirect,
//						'redirect_page_id' => $redirectLocalizationId,
//						'redirect_page_path' => $path,
//					);
//
//					// checking if redirect localization has another redirect
//					$linkElement = $redirectLocalization->getRedirect();
//					$pageLocalization = $redirectLocalization;
//					$parentData = $data;
//
//					break;
//				// parse relative redirect
//				case LinkReferencedElement::RESOURCE_RELATIVE_PAGE:
//					/* @var $pageLocalization Entity\PageLocalization */
//
//					// getting children
//					$pageLocalizationChildrenCollection = $pageLocalization->getChildren();
//					if ( ! $pageLocalizationChildrenCollection instanceof \Doctrine\Common\Collections\Collection) {
//						unset($linkElement);
//						break;
//					}
//
//					$pageLocalizationChildren = $pageLocalizationChildrenCollection->getValues();
//
//					// selecting first or last children
//					if ($linkElement->getHref() == LinkReferencedElement::RELATIVE_FIRST
//							&& ! empty($pageLocalizationChildren)) {
//
//						$redirectLocalization = array_shift($pageLocalizationChildren);
//					} elseif ($linkElement->getHref() == LinkReferencedElement::RELATIVE_LAST
//							&& ! empty($pageLocalizationChildren)) {
//
//						$redirectLocalization = array_pop($pageLocalizationChildren);
//					} else {
//						unset($linkElement);
//						break;
//					}
//
//					if ( ! $redirectLocalization instanceof Entity\PageLocalization) {
//						unset($linkElement);
//						break;
//					}
//
//					$redirect = true;
//					$redirectLocalizationId = $redirectLocalization->getId();
//					$path = '/' . $redirectLocalization->getLocale() . $redirectLocalization->getFullPath(Path::FORMAT_BOTH_DELIMITERS);
//
//					$data = array(
//						'redirect' => $redirect,
//						'redirect_page_id' => $redirectLocalizationId,
//						'redirect_page_path' => $path,
//					);
//
//					// checking if redirect localization has another redirect
//					$linkElement = $redirectLocalization->getRedirect();
//					$pageLocalization = $redirectLocalization;
//					$parentData = $data;
//
//					break;
//
//				default:
//					unset($linkElement);
//					break;
//			}
//		} while ($linkElement instanceof LinkReferencedElement);
//
//		if ( ! empty($data)) {
//			return $data;
//		} else {
//			return $parentData;
//		}
	}

	/**
	 * @return BlockCollection
	 */
	protected function getBlockCollection()
	{
		return $this->container['cms.pages.blocks.collection'];
	}

	/**
	 * @return PageRequest
	 */
	protected function getPageRequest()
	{
		// @TODO: looks lame
		return $this->pageRequest ? $this->pageRequest
				: $this->container['cms.pages.request.view'];
	}

	/**
	 * @return ProcessorInterface
	 */
	protected function getLayoutProcessor()
	{
		return $this->container['cms.pages.layout_processor'];
	}

	/**
	 * @return PageResponse
	 */
	public function getPageResponse()
	{
		if ($this->response === null) {
			$this->response = $this->createPageResponse();

			$this->response->setContext(new ResponseContext());
		}

		return $this->response;
	}

	/**
	 * @return PageResponse
	 */
	protected function createPageResponse()
	{
		return new PageResponse();
	}
}