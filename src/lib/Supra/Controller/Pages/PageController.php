<?php

namespace Supra\Controller\Pages;

use Supra\Controller\ControllerAbstraction;
use Supra\Response;
use Supra\Response\ResponseInterface;
use Supra\Request\RequestInterface;
use Supra\Controller\Layout;
use Supra\Database\Doctrine;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Response\PlaceHolder;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Uri\Path;
use Supra\Response\ResponseContext;
use Supra\Response\ResponseContextLocalProxy;
use Supra\Controller\Pages\Event\BlockEvents;
use Supra\Controller\Pages\Event\BlockEventsArgs;
use Supra\Cache\CacheGroupManager;
use Supra\Controller\Exception\AuthorizationRequiredException;
use Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;
/**
 * Page controller
 * @method PageRequest getRequest()
 * @method Response\HttpResponse getResponse()
 */
class PageController extends ControllerAbstraction
{

	const SCHEMA_DRAFT = '#cms';
	const SCHEMA_PUBLIC = '#public';
	const SCHEMA_AUDIT = '#audit';
	const EVENT_POST_PREPARE_CONTENT = 'postPrepareContent';
	const EVENT_BLOCK_START = 'blockStartExecuteEvent';
	const EVENT_BLOCK_END = 'blockEndExecuteEvent';
	const CACHE_GROUP_NAME = 'Supra\Controller\Pages';

	//public static $knownSchemaNames = array(self::SCHEMA_DRAFT, self::SCHEMA_AUDIT, self::SCHEMA_PUBLIC);
	public static $knownSchemaNames = array(self::SCHEMA_DRAFT, self::SCHEMA_PUBLIC);

	/**
	 * List of block controllers
	 * @var array
	 */
	private $blockControllers = array();

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
	 * Binds entity manager
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Downcasts receives request object into 
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	public function prepare(RequestInterface $request, ResponseInterface $response)
	{
		// Downcast to local request object
		if ( ! $request instanceof namespace\Request\PageRequest) {
			$request = new namespace\Request\PageRequestView($request);
		}

		$request->setDoctrineEntityManager($this->getEntityManager());

		parent::prepare($request, $response);
	}

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		//TODO: create listener which would add each loaded entity as child of this
		return ObjectRepository::getEntityManager($this);
	}

	/**
	 * Execute controller
	 */
	public function execute()
	{
		$request = $this->getRequest();
		$response = $this->getResponse();

		$localization = null;

		// Check redirect for public calls
		if ($request instanceof Request\PageRequestView) {

			try {
				$localization = $request->getPageLocalization();
				/* @var $localization Entity\PageLocalization */

				if ( ! $localization instanceof Entity\PageLocalization) {
					$this->log->warn("Page received from PageRequestView is not of PageLocalization instance, requested uri: ", $request->getActionString(), ', got ', (is_object($localization) ? get_class($localization) : gettype($localization)));
					throw new ResourceNotFoundException("Wrong page instance received");
				}

				$isLimited = $localization->getPathEntity()
						->isLimited();

				if ($isLimited) {
					$sessionManager = ObjectRepository::getSessionManager($this);
					$currentUser = $sessionManager->getAuthenticationSpace()
							->getUser();

					if ( ! $currentUser instanceof \Supra\User\Entity\User) {
						throw new AuthorizationRequiredException();
					}
				}

				$redirect = $localization->getRedirect();
                //TODO: 404 on redirect loop? Currently shows page without redirect
				if ($redirect instanceof LinkReferencedElement) {

					ObjectRepository::setCallerParent($redirect, $this);

					$redirectData = $this->getRedirectData($localization);
					$redirectLoop = empty($redirectData) ? true : false;
					$resource = $redirect->getResource();

					if (($resource == LinkReferencedElement::RESOURCE_LINK) || ! $redirectLoop) {
						
						$location = '/';

						// If redirect is external URL, add scheme for links like "www.example.org"
						if ($resource == LinkReferencedElement::RESOURCE_LINK) {
							$location = $redirect->getUrl($this);
							$scheme = parse_url($location, PHP_URL_SCHEME);
							$firstChar = substr($location, 0, 1);

							if (empty($scheme) && $firstChar != '/') {
								$location = 'http://' . $location;
							}
						} elseif ( ! empty($redirectData['redirect_page_path'])) {
							$location = $redirectData['redirect_page_path'];
						} else {
							$location = $redirect->getUrl($this);
						}
						
						$response->redirect($location);

						return;
					}
				}
				// page requires user to be logged-in
			} catch (AuthorizationRequiredException $e) {
				try {
					$this->getLocalizationByPath('login');
					$response->setCode(403);
				} catch (ResourceNotFoundException $e404) {
					throw $e;
				}
				// page not found
			} catch (ResourceNotFoundException $e) {
				try {
					//TODO: hardcoded for now
					$this->getLocalizationByPath('404');
					$response->setCode(404);

					// Throw the original exception if 404 page is not found
				} catch (ResourceNotFoundException $e404) {
					throw $e;
				}
			}
		}

		// Continue processing
		$layout = $request->getLayout();

		if (is_null($layout)) {
			throw new Exception\LayoutNotFound("No layout found for page {$localization}");
		}

		$page = $request->getPage();

		$this->findBlockCache();

		$this->findBlockControllers();
		\Log::debug("Block controllers found for {$page}");

		// Initialize block property set so no additional queries are registered
		// for the first block which does it
		$request->getBlockPropertySet();

		$this->prepareBlockControllers();
		\Log::debug("Blocks prepared for {$page}");

		// The cache might be context dependent
		$this->findContextDependentBlockCache();

		$this->executeBlockControllers();
		\Log::debug("Blocks executed for {$page}");

		$eventArgs = new Event\PostPrepareContentEventArgs($this);
		$eventArgs->request = $this->getRequest();
		$eventArgs->response = $this->getResponse();

		$eventManager = ObjectRepository::getEventManager($this);
		$eventManager->fire(self::EVENT_POST_PREPARE_CONTENT, $eventArgs);

		$blockId = $request->getBlockRequestId();
		
		if (is_null($blockId)) {
			$placeResponses = $this->getPlaceResponses($request);
			$this->processLayout($layout, $placeResponses);
		} else {
			
			$collectResponses = function(Entity\Abstraction\Block $block, BlockController $blockController)
				use ($blockId, $response) {
					if ($block->getId() === $blockId || $block->getComponentClass() === $blockId) {
						$response->output($blockController->getResponse());
					}
				};
			
			$this->iterateBlocks($collectResponses, Listener\BlockExecuteListener::ACTION_RESPONSE_COLLECT);
			
			$response->flush();
		}
		
		
		\Log::debug("Layout {$layout} processed and output to response for {$page}");
	}

	/**
	 * Returns redirect data. Redirect localization id, path
	 * 
	 * @param Entity\PageLocalization $pageLocalization
	 * @return array
	 */
	public function getRedirectData(Entity\PageLocalization $pageLocalization)
	{
		$redirectPageIds = $data = $parentData = array();

		$linkElement = $pageLocalization->getRedirect();
		$em = $this->getEntityManager();

		$redirect = false;
		$redirectLocalizationId = null;
		
		if ( ! $linkElement instanceof LinkReferencedElement) {
			return array();
		}

		do {
			$pageLocalizationId = $pageLocalization->getId();
			
			// check if localization id is not in loop
			if (in_array($pageLocalizationId, $redirectPageIds)) {

				$message = "Looks like page (#id: {$pageLocalizationId}, #title: \"{$pageLocalization->getTitle()}\") " .
						'is linking to another page which already was in redirect chain.';

				\Log::error($message);

				//			$this->writeAuditLog($message);
				return array();
			}


			$redirectPageIds[] = $pageLocalizationId;

			$redirectPageId = $redirectLocalization = null;
			$resource = $linkElement->getResource();
			
			$data = array();
			
			switch ($resource) {
				// parse fixed redirect
				case LinkReferencedElement::RESOURCE_PAGE:
					// searching for redirect page
					$redirectPageId = $linkElement->getPageId();
					if (empty($redirectPageId)) {
						unset($linkElement);
						break;
					}
					
					$redirectPage = $em->getRepository(Entity\Abstraction\AbstractPage::CN())
							->findOneById($redirectPageId);

					if ( ! $redirectPage instanceof Entity\Abstraction\AbstractPage) {
						unset($linkElement);
						break;
					}
					
					// redirect localization
					$redirectLocalization = $redirectPage->getLocalization($pageLocalization->getLocale());

					if ( ! $redirectLocalization instanceof Entity\PageLocalization) {
						unset($linkElement);
						break;
					}

					$redirect = true;
					$redirectLocalizationId = $redirectLocalization->getId();
					$path = '/' . $redirectLocalization->getLocale()
							. $redirectLocalization->getFullPath(Path::FORMAT_BOTH_DELIMITERS);

					$data = array(
						'redirect' => $redirect,
						'redirect_page_id' => $redirectLocalizationId,
						'redirect_page_path' => $path,
					);

					// checking if redirect localization has another redirect
					$linkElement = $redirectLocalization->getRedirect();
					$pageLocalization = $redirectLocalization;
					$parentData = $data;
					
					break;
				// parse relative redirect
				case LinkReferencedElement::RESOURCE_RELATIVE_PAGE:
					/* @var $pageLocalization Entity\PageLocalization */

					// getting children
					$pageLocalizationChildrenCollection = $pageLocalization->getChildren();
					if ( ! $pageLocalizationChildrenCollection instanceof \Doctrine\Common\Collections\Collection) {
						unset($linkElement);
						break;
					}

					$pageLocalizationChildren = $pageLocalizationChildrenCollection->getValues();

					// selecting first or last children
					if ($linkElement->getHref() == LinkReferencedElement::RELATIVE_FIRST
							&& ! empty($pageLocalizationChildren)) {

						$redirectLocalization = array_shift($pageLocalizationChildren);
					} elseif ($linkElement->getHref() == LinkReferencedElement::RELATIVE_LAST
							&& ! empty($pageLocalizationChildren)) {

						$redirectLocalization = array_pop($pageLocalizationChildren);
					} else {
						unset($linkElement);
						break;
					}

					if ( ! $redirectLocalization instanceof Entity\PageLocalization) {
						unset($linkElement);
						break;
					}

					$redirect = true;
					$redirectLocalizationId = $redirectLocalization->getId();
					$path = '/' . $redirectLocalization->getLocale() . $redirectLocalization->getFullPath(Path::FORMAT_BOTH_DELIMITERS);
					
					$data = array(
						'redirect' => $redirect,
						'redirect_page_id' => $redirectLocalizationId,
						'redirect_page_path' => $path,
					);

					// checking if redirect localization has another redirect
					$linkElement = $redirectLocalization->getRedirect();
					$pageLocalization = $redirectLocalization;
					$parentData = $data;

					break;

				default:
					unset($linkElement);
					break;
			}
		} while ($linkElement instanceof LinkReferencedElement);

		if ( ! empty($data)) {
			return $data;
		} else {
			return $parentData;
		}
	}

	/**
	 * @param Entity\ThemeLayout $layout
	 * @param array $blocks array of block responses
	 */
	protected function processLayout(Entity\ThemeLayout $layout, array $placeResponses)
	{
		$layoutProcessor = $this->getLayoutProcessor();

		$layoutProcessor->setTheme($layout->getTheme());

		$layoutSrc = $layout->getFilename();

		$response = $this->getResponse();

		$layoutProcessor->setRequest($this->request);
		$layoutProcessor->setResponse($response);

		$layoutProcessor->process($response, $placeResponses, $layoutSrc);
	}

	/**
	 * @return Layout\Processor\ProcessorInterface
	 */
	public function getLayoutProcessor()
	{
		$processor = new Layout\Processor\TwigProcessor();

		ObjectRepository::setCallerParent($processor, $this);

		return $processor;
	}

	/**
	 * Generate response object
	 * @param RequestInterface
	 * @return ResponseHttpResponse
	 */
	public function createResponse(RequestInterface $request)
	{
		return new Response\HttpResponse();
	}

	/**
	 * 
	 */
	protected function findBlockCache()
	{
		$request = $this->getRequest();

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
					$blockCache = $configuration->cache;

					if ($blockCache instanceof Configuration\BlockControllerCacheConfiguration) {

						$blockId = $block->getId();
						$blockCacheRequests[$blockId] = $blockCache;

						$self->searchResponseCache($blockId, $localization, $block);
					}
				};

		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($cacheSearch, Listener\BlockExecuteListener::ACTION_CACHE_SEARCH);
	}

	/**
	 * Create block controllers
	 */
	protected function findBlockControllers()
	{
		$request = $this->getRequest();
		$page = $request->getPage();
		$blockContentCache = &$this->blockContentCache;

		// function which adds controllers for the block
		$controllerFactory = function(Entity\Abstraction\Block $block, &$blockController) use ($page, &$blockContentCache) {

					// Skip controller creation if cache found
					$blockId = $block->getId();
					if (array_key_exists($blockId, $blockContentCache)) {
						$blockController = new CachedBlockController($blockContentCache[$blockId]);

						return;
					}

					$blockController = $block->createController();

					if (empty($blockController)) {
						throw new Exception\InvalidBlockException('Block controller was not found');
					}
				};

		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($controllerFactory, Listener\BlockExecuteListener::ACTION_CONTROLLER_SEARCH);
	}

	/**
	 * Prepare block controllers
	 */
	protected function prepareBlockControllers()
	{
		$request = $this->getRequest();

		$responseContext = new ResponseContext();

		$this->getResponse()
				->setContext($responseContext);

		$blockContentCache = $this->blockContentCache;
		$blockCacheRequests = $this->blockCacheRequests;

		// function which adds controllers for the block
		$prepare = function(Entity\Abstraction\Block $block, BlockController $blockController) use ($request, $responseContext, &$blockContentCache, &$blockCacheRequests) {

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
		$this->iterateBlocks($prepare, Listener\BlockExecuteListener::ACTION_CONTROLLER_PREPARE);
	}

	/**
	 * Late cache check for blocks which cache key depends on response context values
	 */
	protected function findContextDependentBlockCache()
	{
		$request = $this->getRequest();

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
		$response = $this->getResponse();
		$context = $response->getContext();
		$self = $this;

		$cacheSearch = function(Entity\Abstraction\Block $block, BlockController &$blockController)
				use ($self, $localization, $cacheGroupManager, $cache, &$blockContentCache, &$blockCacheRequests, $request, $context) {

					$blockId = $block->getId();

					$self->searchResponseCache($blockId, $localization, $block, $context);
				};

		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($cacheSearch, Listener\BlockExecuteListener::ACTION_DEPENDENT_CACHE_SEARCH);
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
		$eventManager = ObjectRepository::getEventManager($this);
		$blockContentCache = $this->blockContentCache;

		// function which adds controllers for the block
		$prepare = function(Entity\Abstraction\Block $block, BlockController $blockController) use ($eventManager, &$blockContentCache) {

					$blockId = $block->getId();
					if (array_key_exists($blockId, $blockContentCache)) {
						return;
					}

					// It is important to prepare the twig helper for each block controller right before execution
					$blockController->prepareTwigEnvironment();

					$blockController->execute();
				};

		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($prepare, Listener\BlockExecuteListener::ACTION_CONTROLLER_EXECUTE);
	}

	/**
	 * Creates place holder response object
	 * @param Entity\Abstraction\AbstractPage $page
	 * @param Entity\Abstraction\PlaceHolder $placeHolder
	 * @return PlaceHolder\PlaceHolderResponse
	 */
	public function createPlaceResponse(Entity\Abstraction\AbstractPage $page, Entity\Abstraction\PlaceHolder $placeHolder)
	{
		$response = null;

		if ($this->request instanceof namespace\Request\PageRequestEdit) {
			$response = new PlaceHolder\PlaceHolderResponseEdit();
		} else {
			$response = new PlaceHolder\PlaceHolderResponseView();
		}

		$response->setPlaceHolder($placeHolder);

		return $response;
	}

	/**
	 * Iterates through blocks and returs array of place holder responses
	 * @return array
	 */
	protected function getPlaceResponses()
	{
		$placeResponses = array();
		$request = $this->getRequest();
		/* @var $request PageRequest */

		$placeHolders = $request->getPlaceHolderSet();
		$page = $request->getPage();

		$localization = $request->getPageLocalization();

		$finalPlaceHolders = $placeHolders->getFinalPlaceHolders();

		foreach ($finalPlaceHolders as $name => $placeHolder) {
			$placeResponses[$name] = $this->createPlaceResponse($page, $placeHolder);
		}

		$blockCacheRequests = &$this->blockCacheRequests;
		$cache = ObjectRepository::getCacheAdapter($this);
		$log = $this->log;

		$collectResponses = function(Entity\Abstraction\Block $block, BlockController $blockController)
				use (&$placeResponses, $localization, $finalPlaceHolders, $request, $blockCacheRequests, $cache, $log) {

					$response = $blockController->getResponse();
					$blockId = $block->getId();

					$placeName = $block->getPlaceHolder()
							->getName();

					if ( ! isset($placeResponses[$placeName])) {

						//TODO: what is the action on such case?
						throw new Exception\LogicException("Logic problem – final place holder by name $placeName is not found");
					}

					$placeResponse = $placeResponses[$placeName];

					//TODO: move to separate method
					if ($request instanceof Request\PageRequestEdit) {
						$blockName = $block->getComponentName();

						if ($blockController instanceof BrokenBlockController) {
							$blockName = $blockController::BLOCK_NAME;
						}

						$prefixContent = '<div id="content_' . $blockName . '_' . $blockId
								. '" class="yui3-content yui3-content-' . $blockName
								. ' yui3-content-' . $blockName . '-' . $blockId . '">';

						$placeResponse->output($prefixContent);
					}

					$placeResponse->output($response);

					if ($request instanceof Request\PageRequestEdit) {
						$placeResponse->output('</div>');
					}

					if (isset($blockCacheRequests[$blockId])) {
						$blockCache = $blockCacheRequests[$blockId];
						$context = $response->getContext();
						$cacheKey = $blockCache->getCacheKey($localization, $block, $context);
						$lifetime = $blockCache->getLifetime();

						try {
							$serializedResponse = serialize($response);
							$cache->save($cacheKey, $serializedResponse, $lifetime);
						} catch (\Exception $e) {
							$blockName = $block->getComponentName();
							$log->error("Could not serialize response of block $blockName: ", $e->__toString());
						}
					}
				};

		// Iterates through all blocks and collects placeholder responses
		$this->iterateBlocks($collectResponses, Listener\BlockExecuteListener::ACTION_RESPONSE_COLLECT);

		return $placeResponses;
	}

	/**
	 * Iteration funciton for specific array of blocks
	 * @param \Closure $function
	 * @return array
	 */
	protected function iterateBlocks(\Closure $function, $eventAction = null)
	{
		$blocks = $this->getRequest()
				->getBlockSet();

		$eventManager = ObjectRepository::getEventManager($this);

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

				if ( ! is_null($eventAction)) {
					$eventArgs = new BlockEventsArgs($blockController);
					$eventArgs->block = $block;
					// Assigned by reference because "null" can change to object after closure execution
					$eventArgs->blockController = &$blockController;
					$eventArgs->actionType = $eventAction;
					$eventArgs->request = $this->request;
					$eventArgs->blockRequest = ($this->getRequest()->getBlockRequestId() !== null);

					$eventManager->fire(BlockEvents::blockStartExecuteEvent, $eventArgs);

					$blockTimeStart = microtime(true);
				}

				// NB! Block controller variable might be rewritten in the function
				$return[$index] = $function($block, $blockController);

				if (
						$blockController instanceof BlockController &&
						$blockController->hadException()
				) {

					// Don't cache failed blocks 
					unset($this->blockCacheRequests[$blockId]);

					// Add exception to blockEndExecute event.
					$eventArgs->exception = $blockController->hadException();
				}

				if ( ! is_null($eventAction)) {
					
					if ( ! is_null($blockController)) {
						$eventArgs->setCaller($blockController);
					}
					
					$blockTimeEnd = microtime(true);
					$blockExecutionTime = $blockTimeEnd - $blockTimeStart;
					$eventArgs->duration = $blockExecutionTime;
					$eventArgs->cached = isset($this->blockContentCache[$blockId]);
					$eventManager->fire(BlockEvents::blockEndExecuteEvent, $eventArgs);
				}
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
		$request = $this->getRequest();

		$request->resetPageLocalization();
		$request->setPath(new Path($pathString));

		$request->getPageLocalization();
	}

}
