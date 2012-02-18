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
use Supra\Controller\Pages\Event\BlockEvents;
use Supra\Controller\Pages\Event\BlockEventsArgs;
use Supra\Cache\CacheGroupManager;
use Supra\Controller\Exception\AuthorizationRequiredException;

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

		// Check redirect for public calls
		if ($request instanceof Request\PageRequestView) {

			$localization = null;

			try {
				$localization = $request->getPageLocalization();
				/* @var $localization Entity\PageLocalization */
				
				if ( ! $localization instanceof Entity\PageLocalization) {
					$this->log->warn("Page received from PageRequestView is not of PageLocalization instance, requested uri: ",
							$request->getActionString(), ', got ',
							(is_object($localization) ? get_class($localization) : gettype($localization)));
					throw new ResourceNotFoundException("Wrong page instance received");
				}
				
				//
				$isLimited = $localization->isLimitedAccessPage();
				$isParentLimited = $localization->getPathEntity()
						->isLimited();
				
				if ($isLimited || $isParentLimited) {
					$sessionManager = ObjectRepository::getSessionManager($this);
					$currentUser = $sessionManager->getAuthenticationSpace()
							->getUser();
					
					if ( ! $currentUser instanceof \Supra\User\Entity\User) {
						throw new AuthorizationRequiredException();
					}
				}
				
				$redirect = $localization->getRedirect();
				if ($redirect instanceof Entity\ReferencedElement\LinkReferencedElement) {
					//TODO: any validation? skipping? loop check?

					ObjectRepository::setCallerParent($redirect, $this);

					$location = $redirect->getUrl($this);
					if ( ! is_null($location)) {
						
						$resource = $redirect->getResource();
						
						// If redirect is external URL, add scheme for links like "www.example.org"
						if ($resource == Entity\ReferencedElement\LinkReferencedElement::RESOURCE_LINK) {
							
							$scheme = parse_url($location, PHP_URL_SCHEME);
							$firstChar = substr($location, 0, 1);
							
							if (empty($scheme) && $firstChar != '/') {
								$location = 'http://' . $location;
							}
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
//		$blocks = $request->getBlockSet();
		$layout = $request->getLayout();
		$page = $request->getPage();

//		$places = $request->getPlaceHolderSet();
		
		$this->findBlockCache();
		
		$this->findBlockControllers();
		\Log::debug("Block controllers found for {$page}");

		$this->prepareBlockControllers();
		\Log::debug("Blocks prepared for {$page}");

		$this->executeBlockControllers();
		\Log::debug("Blocks executed for {$page}");

		$placeResponses = $this->getPlaceResponses($request);
		
		$eventArgs = new Event\PostPrepareContentEventArgs();
		$eventArgs->request = $this->getRequest();
		$eventArgs->response = $this->getResponse();
		
		$eventManager = ObjectRepository::getEventManager($this);
		$eventManager->fire(self::EVENT_POST_PREPARE_CONTENT, $eventArgs);
		
		$this->processLayout($layout, $placeResponses);
		\Log::debug("Layout {$layout} processed and output to response for {$page}");
		
	}

	/**
	 * @param Entity\Layout $layout
	 * @param array $blocks array of block responses
	 */
	protected function processLayout(Entity\Layout $layout, array $placeResponses)
	{
		$layoutProcessor = $this->getLayoutProcessor();
		$layoutSrc = $layout->getFile();
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
		$processor->setLayoutDir(SUPRA_TEMPLATE_PATH);

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
		
		$cacheSearch = function(Entity\Abstraction\Block $block) 
			use ($localization, $cacheGroupManager, $cache, &$blockContentCache, &$blockCacheRequests, $request) {
			
			$blockClass = $block->getComponentClass();
			$blockCollection = BlockControllerCollection::getInstance();
			$configuration = $blockCollection->getBlockConfiguration($blockClass);
			$blockCache = $configuration->cache;
			
			if ($blockCache instanceof Configuration\BlockControllerCacheConfiguration) {
				
				$cacheKey = $blockCache->getCacheKey($localization, $block);
				
				if (empty($cacheKey)) {
					return;
				}
				
				$content = $cache->fetch($cacheKey);
				$blockId = $block->getId();
				$responseCache = null;
				
				if ($content !== false) {
					
					$responseCache = unserialize($content);
					
					if ( ! empty($responseCache)) {
						$blockContentCache[$blockId] = $responseCache;
						$request->skipBlockPropertyLoading($blockId);
					}
				}
				
				if (empty($responseCache)) {
					$blockCacheRequests[$blockId] = $blockCache;
				}
			}
		};
		
		// Iterates through all blocks and calls the function passed
		$this->blockControllers = $this->iterateBlocks($cacheSearch);
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
		$controllerFactory = function(Entity\Abstraction\Block $block) use ($page, &$blockContentCache) {
			
			// Skip controller creation if cache found
			$blockId = $block->getId();
			if (array_key_exists($blockId, $blockContentCache)) {
				return new CachedBlockController($blockContentCache[$blockId]);
			}
			
			$blockController = $block->createController();

			if (empty($blockController)) {
				throw new Exception\InvalidBlockException('Block controller was not found');
			}

//			$blockController->setPage($page);

			return $blockController;
		};

		// Iterates through all blocks and calls the function passed
		$this->blockControllers = $this->iterateBlocks($controllerFactory);
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

		// function which adds controllers for the block
		$prepare = function(Entity\Abstraction\Block $block, BlockController $blockController) use ($request, $responseContext, &$blockContentCache) {
			
			$blockId = $block->getId();
			if (array_key_exists($blockId, $blockContentCache)) {
				return;
			}
			
			$block->prepareController($blockController, $request, $responseContext);
		};

		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($prepare);
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
			$blockController->prepareTwigHelper();

			$eventArgs = new BlockEventsArgs();
			$eventArgs->blockClass = $block->getComponentClass();

			$eventManager->fire(BlockEvents::blockStartExecuteEvent, $eventArgs);

			$blockTimeStart = microtime(true);
			$blockController->execute();
			$blockTimeEnd = microtime(true);
			$blockExecutionTime = $blockTimeEnd - $blockTimeStart;
			$eventArgs->duration = $blockExecutionTime;
			$eventManager->fire(BlockEvents::blockEndExecuteEvent, $eventArgs);

		};

		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($prepare);
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

						$prefixCountent = '<div id="content_' . $blockName . '_' . $blockId
								. '" class="yui3-content yui3-content-' . $blockName
								. ' yui3-content-' . $blockName . '-' . $blockId . '">';

						$placeResponse->output($prefixCountent);
					}

					$placeResponse->output($response);

					if ($request instanceof Request\PageRequestEdit) {
						$placeResponse->output('</div>');
					}
					
					if (isset($blockCacheRequests[$blockId])) {
						$blockCache = $blockCacheRequests[$blockId];
						$cacheKey = $blockCache->getCacheKey($localization, $block);
						$lifetime = $blockCache->getLifetime();
						
						try {
							$serializedResponse = serialize($response);
							$cache->save($cacheKey, $serializedResponse, $lifetime);
						} catch (\Exception $e) {
							$blockName = $block->getComponentName();
							$log->error("Could not serialize response of block $blockName");
						}
						
					}
				};

		// Iterates through all blocks and collects placeholder responses
		$this->iterateBlocks($collectResponses);

		return $placeResponses;
	}

	/**
	 * Iteration funciton for specific array of blocks
	 * @param \Closure $function
	 * @return array
	 */
	protected function iterateBlocks(\Closure $function)
	{
		$blocks = $this->getRequest()
				->getBlockSet();

		$return = array();

		/* @var $block Entity\Abstraction\Block */
		foreach ($blocks as $index => $block) {

			$blockController = null;
			if (isset($this->blockControllers[$index])) {
				$blockController = $this->blockControllers[$index];
			}

			try {
				$return[$index] = $function($block, $blockController);
			} catch (Exception\InvalidBlockException $e) {
				\Log::warn("Skipping block $block because of raised SkipBlockException: {$e->getMessage()}");
				unset($blocks[$index]);
			}
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