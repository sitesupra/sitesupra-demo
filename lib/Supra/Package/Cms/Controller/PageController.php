<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\Templating\TwigTemplating;
use Supra\Package\Cms\Pages\Block\CachedBlockController;
use Supra\Package\Cms\Pages\Block\Mapper\CacheMapper;
use Supra\Package\Cms\Pages\PageExecutionContext;
use Supra\Package\Cms\Pages\Response\ResponsePart;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Supra\Core\Controller\Controller;
use Supra\Package\Cms\Entity\Abstraction\PlaceHolder;
use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Pages\Exception\LayoutNotFound;
use Supra\Package\Cms\Pages\BlockController;
use Supra\Package\Cms\Pages\Block\BlockCollection;
use Supra\Package\Cms\Pages\Response\PageResponse;
use Supra\Package\Cms\Pages\Response\PlaceHolderResponseView;
use Supra\Package\Cms\Pages\Response\PlaceHolderResponseEdit;
use Supra\Package\Cms\Pages\Response\ResponseContext;
use Supra\Package\Cms\Pages\Request\PageRequest;
use Supra\Package\Cms\Pages\Request\PageRequestEdit;
use Supra\Package\Cms\Pages\Request\PageRequestView;

class PageController extends Controller
{
	/**
	 * @var PageResponse
	 */
	protected $pageResponse;

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
	 * Index action.
	 * Creates PageRequestView object, then runs main execute action.
	 *
	 * @param Request $request
	 */
	public function indexAction(Request $request)
	{
		return $this->execute(
				$this->createPageRequest($request)
		);
	}

	/**
	 * Main execute action.
	 *
	 * @param PageRequest $pageRequest
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws ResourceNotFoundException when requested page not found / not active
	 * @throws LayoutNotFound when template layout not found
	 * @throws \UnexpectedValueException
	 */
	public function execute(PageRequest $pageRequest)
	{
		$this->pageRequest = $pageRequest;
		$this->pageResponse = $this->createPageResponse();

		$localization = $pageRequest->getLocalization();

		if (! $localization instanceof Localization) {
			throw new \UnexpectedValueException(sprintf(
					'Expecting Localization object only [%s] received.',
					get_class($localization)
			));
		}

		if ($pageRequest instanceof PageRequestView) {
			if ($localization->hasRedirectTarget()) {

				$redirectUrl = $localization->getRedirectTarget()
						->getRedirectUrl();

				if (! empty($redirectUrl)) {
					 //@TODO: check for redirect loops
					return new RedirectResponse($redirectUrl);
				}

				throw new ResourceNotFoundException;
			}
		}

		$layout = $pageRequest->getLayout();

		if ($layout === null) {
			throw new LayoutNotFound(sprintf(
					'No layout found for page localization [%s]',
					$localization->getId()
			));
		}

		$templating = $this->container->getTemplating();

		if ($templating instanceof TwigTemplating) {
			$templating->getExtension('supraPage')
					->setPageExecutionContext(new PageExecutionContext($this->pageRequest, $this));
		}

		// searching for blocks cache
		$this->findBlockCache();

		// searching and instantiating controllers
		$this->findBlockControllers();

		// prepare controllers
		$this->prepareBlockControllers();

		// some of the block cache may be context-dependent
		// and as context may change after preparing we're searching for cache again
		$this->findContextDependentBlockCache();

		// execute controllers
		$this->executeBlockControllers();

		// stores cache
		$this->cacheBlockResponses();

		// create placeholder responses, process page layout
		$this->getLayoutProcessor()->process(
				$layout->getFilename(),
				$this->pageResponse,
				$this->createPlaceResponses()
		);

		return $this->pageResponse;
	}

	/**
	 * Searches for block response cache.
	 *
	 * @return void
	 */
	protected function findBlockCache()
	{
		// Don't search for cache in CMS
		if (! $this->pageRequest instanceof PageRequestView) {
			return;
		}

		$localization = $this->pageRequest->getLocalization();
		$blockCacheRequests = &$this->blockCacheRequests;
		$self = $this;

		$this->iterateBlocks(function(Block $block)	use ($self, $localization, &$blockCacheRequests) {

				$blockClass = $block->getComponentClass();

				$configuration = $self->getBlockCollection()->getConfiguration($blockClass);

				if ($configuration->getCache() instanceof CacheMapper) {

					$blockId = $block->getId();
					$blockCacheRequests[$blockId] = $configuration->getCache();

					$self->loadResponseCache($block, $localization);
				}
		});
	}

	/**
	 * Create block controllers
	 */
	protected function findBlockControllers()
	{
		$blockContentCache = &$this->blockContentCache;

		$blockCollection = $this->getBlockCollection();

		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks(function(Block $block, &$blockController) use ($blockCollection, &$blockContentCache) {
			// Skip controller creation if cache found
			$blockId = $block->getId();

			if (array_key_exists($blockId, $blockContentCache)) {
				$blockController = new CachedBlockController($blockContentCache[$blockId], $block);
				return;
			}

			$blockController = $blockCollection->createController($block);
		});
	}

	/**
	 * Prepare block controllers
	 */
	protected function prepareBlockControllers()
	{
		$pageRequest = $this->pageRequest;

		$this->iterateBlocks(function(Block $block, BlockController $blockController) use ($pageRequest) {
				$blockController->prepare($pageRequest);
		});
	}

	/**
	 * Late cache check for blocks which cache key depends on response context values.
	 *
	 * @return void
	 */
	protected function findContextDependentBlockCache()
	{
		// Don't search for cache in CMS
		if (! $this->pageRequest instanceof PageRequestView) {
			return;
		}
		
		$localization = $this->pageRequest->getLocalization();
		$context = $this->pageResponse->getContext();
		$self = $this;

		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks(function(Block $block) 
				use ($self, $localization, $context) {
						$self->loadResponseCache($block, $localization, $context);
		});
	}

	/**
	 * Executes block controllers
	 */
	protected function executeBlockControllers()
	{
		$blockContentCache = $this->blockContentCache;

		$this->iterateBlocks(function(Block $block, BlockController $controller) use (&$blockContentCache) {
			// skip cached
			if (! array_key_exists($block->getId(), $blockContentCache)) {
				$controller->execute();
			}
		});
	}

	/**
	 * Caches block responses
	 */
	protected function cacheBlockResponses()
	{
		$cacheRequests = $this->blockCacheRequests;
		$localization = $this->pageRequest->getLocalization();
		$logger = $this->container->getLogger();
		$cache = $this->container->getCache();

		$this->iterateBlocks(function(Block $block, BlockController $controller) 
				use (&$cacheRequests, $localization, $cache, $logger) {

			$blockId = $block->getId();

			if (! isset($cacheRequests[$blockId])) {
				return;
			}
			
			$cacheConfig = $cacheRequests[$blockId];
			
			$response = $controller->getResponse();
			$context = $response->getContext();
			
			try {
				$cache->store(
						'block_cache',
						$cacheConfig->getCacheKey($localization, $block, $context),
						serialize($response),
						time(),
						$cacheConfig->getLifetime()
				);
			} catch (\Exception $e) {
				$logger->error(sprintf(
						"Failed to store cache for [%s], got exception [%s]",
						get_class($controller),
						$e->getMessage()
				));
			}
		});
	}
	
	/**
	 * @param Block $block
	 * @param Localization $localization
	 * @param ResponseContext $context
	 * @return void
	 */
	private function loadResponseCache(
			Block $block,
			Localization $localization,
			ResponseContext $context = null
	) {

		$blockId = $block->getId();

		if (! array_key_exists($blockId, $this->blockCacheRequests)) {
			return;
		}

		$blockCache = $this->blockCacheRequests[$blockId];
		/* @var $blockCache CacheMapper */

		$cacheKey = $blockCache->getCacheKey($localization, $block, $context);

		if (empty($cacheKey)) {
			// Cache disabled, forget the request
			unset($this->blockCacheRequests[$blockId]);
			return;
		}

		$cache = $this->container->getCache();

		$content = $cache->fetch('block_cache', $cacheKey);

		if ($content === false) {
			return;
		}

		$responseCache = unserialize($content);

		if (! $responseCache instanceof ResponsePart) {
			return;
		}

		/* @var $responseCache ResponsePart */


		$this->blockContentCache[$blockId] = $responseCache;

		// Cache found, don't need to cache
		unset($this->blockCacheRequests[$blockId]);

		// Don't load properties
		$this->pageRequest->skipBlockPropertyLoading($blockId);

		// Rewrite controller instance
		$this->blockControllers[$blockId] = new CachedBlockController($this->blockContentCache[$blockId], $block);

		$cachedContext = $responseCache->getContext();

		if ($cachedContext !== null) {
			$cachedContext->flushToContext($this->pageResponse->getContext());
		}
	}

	/**
	 * Creates placeholder response objects with collected block responses.
	 * 
	 * @return PlaceHolderResponse[]
	 */
	protected function createPlaceResponses()
	{
		$placeResponses = array();
		
		$placeHolders = $this->pageRequest->getPlaceHolderSet();

		foreach ($placeHolders->getFinalPlaceHolders() as $name => $placeHolder) {
			$placeResponses[$name] = $this->createPlaceResponse($placeHolder);
		}

		$this->iterateBlocks(function(Block $block, BlockController $blockController) use (&$placeResponses) {

			$response = $blockController->getResponse();
			$placeName = $block->getPlaceHolder()
							->getName();

			if (! isset($placeResponses[$placeName])) {
				throw new \LogicException("Logic problem – final place holder by name [$placeName] is not found.");
			}

			$placeResponses[$placeName]->output($response);
		});

		return $placeResponses;
	}

	/**
	 * Iteration funciton for specific array of blocks.
	 * 
	 * @param \Closure $function
	 * @return array
	 */
	private function iterateBlocks(\Closure $function)
	{
		$return = array();

		foreach ($this->pageRequest->getBlockSet() as $index => $block) {
			/* @var $block Block */

			$blockId = $block->getId();
			/* @var $blockController BlockController */

			if ( ! isset($this->blockControllers[$blockId])) {
				$this->blockControllers[$blockId] = null;
			}

			$blockController = &$this->blockControllers[$blockId];

			$return[$index] = $function($block, $blockController);

			// NB! Block controller variable might be rewritten in the function
			if ($blockController instanceof BlockController
					&& $blockController->hadException()) {

				$this->container->getLogger()->error(
						$blockController->getException()->getMessage()
				);

				// Don't cache failed blocks
				unset($this->blockCacheRequests[$blockId]);
			}
		}

		return $return;
	}

	/**
	 * @return PageRequestView
	 */
	protected function createPageRequest(Request $request)
	{
		$pageRequest = new PageRequestView($request);

		$pageRequest->setContainer($this->container);

		return $pageRequest;
	}

	/**
	 * @return PageResponse
	 */
	protected function createPageResponse()
	{
		$response = new PageResponse();

		$response->setContext(new ResponseContext());

		return $response;
	}

	/**
	 * Creates place holder response object.
	 *
	 * @param PlaceHolder $placeHolder
	 * @return PlaceHolderResponse
	 */
	protected function createPlaceResponse(PlaceHolder $placeHolder)
	{
		return $this->pageRequest instanceof PageRequestEdit
				? new PlaceHolderResponseEdit($placeHolder)
				: new PlaceHolderResponseView($placeHolder);
	}

	/**
	 * @return BlockCollection
	 */
	protected function getBlockCollection()
	{
		return $this->container['cms.pages.blocks.collection'];
	}

	/**
	 * @return ProcessorInterface
	 */
	protected function getLayoutProcessor()
	{
		return $this->container['cms.pages.layout_processor'];
	}
}