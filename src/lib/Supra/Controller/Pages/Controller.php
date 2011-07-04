<?php

namespace Supra\Controller\Pages;

use Supra\Controller\ControllerAbstraction,
		Supra\Response,
		Supra\Response\ResponseInterface,
		Supra\Request\RequestInterface,
		Supra\Controller\Layout,
		Supra\Database\Doctrine,
		Supra\Locale\Data as LocaleData,
		Doctrine\ORM\PersistentCollection,
		Doctrine\ORM\Query\Expr,
		Supra\Controller\NotFoundException,
		Supra\Controller\Pages\Request\HttpEditRequest,
		Supra\Controller\Pages\Response\PlaceHolder as PlaceHolderResponse;

/**
 * Page controller
 */
class Controller extends ControllerAbstraction
{
	/**
	 * Construct
	 */
	public function __construct()
	{
		
	}
	
	/**
	 * Downcasts receives request object into 
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	public function prepare(RequestInterface $request, ResponseInterface $response)
	{
		// Downcast to local request object
		if ( ! $request instanceof namespace\Request\Request) {
			$request = new namespace\Request\RequestView($request);
		}
		
		$em = $this->getDoctrineEntityManager();
		$request->setDoctrineEntityManager($em);
		
		parent::prepare($request, $response);
	}
	
	/**
	 * Overriden to specify correct return class
	 * @return \Supra\Controller\Pages\Request\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	protected function getDoctrineEntityManager()
	{
		$em = Doctrine::getInstance()->getEntityManager();
		
		return $em;
	}

	/**
	 * Execute controller
	 * @return Set\RequestSet
	 */
	public function execute()
	{
		// Current request page
		$page = $this->getRequest()
				->getRequestPageData()
				->getMaster();
		
		$locale = $this->getRequest()
				->getLocale();
		
		$media = $this->getRequest()
				->getMedia();
		
		$requestSet = new Set\RequestSet($locale, $media);
		$requestSet->setDoctrineEntityManager($this->getDoctrineEntityManager());
		$requestSet->setPage($page);
		
		$this->processRequestSet($requestSet);
	}
	
	/**
	 * Processes the request set object
	 * @param Set\RequestSet $requestSet
	 */
	public function processRequestSet(Set\RequestSet $requestSet)
	{
		$blocks = $requestSet->getBlockSet();
		$layout = $requestSet->getLayout();
		$page = $requestSet->getPage();
		
		$places = $requestSet->getPlaceHolderSet();

		$this->getBlockControllers($requestSet);
		\Log::sdebug("Block controllers created for {$page}");
		
		$this->prepareBlockControllers($requestSet);
		\Log::sdebug("Blocks prepared for {$page}");

		$this->outputBlockControllers($requestSet);
		\Log::sdebug("Blocks executed for {$page}");

		$placeResponses = $this->getPlaceResponses($requestSet);

		$this->processLayout($layout, $placeResponses);
		\Log::sdebug("Layout {$layout} processed and output to response for {$page}");
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
		$layoutProcessor->process($response, $placeResponses, $layoutSrc);
	}

	/**
	 * @return Layout\Processor\ProcessorInterface
	 */
	protected function getLayoutProcessor()
	{
		$processor = new Layout\Processor\Html();
		$processor->setLayoutDir(\SUPRA_PATH . 'template');
		return $processor;
	}

	/**
	 * Generate response object
	 * @param RequestInterface
	 * @return Response\Http
	 */
	public function createResponse(RequestInterface $request)
	{
		return new Response\Http();
	}

	/**
	 * Create block controllers
	 * @param Set\RequestSet $requestSet
	 */
	protected function getBlockControllers(Set\RequestSet $requestSet)
	{
		$blocks = $requestSet->getBlockSet();
		$blockPropertySet = $requestSet->getBlockPropertySet();
		
		// function which adds controllers for the block
		$controllerFactory = function(Entity\Abstraction\Block $block) use ($blockPropertySet) {
			$blockController = $block->controllerFactory();
			
			if (empty($blockController)) {
				throw new Exception\InvalidBlockException('Block controller was not found');
			}
			
			$block->setController($blockController);
			
			$blockPropertySubset = $blockPropertySet->getBlockPropertySet($block);
			$blockController->setBlockPropertySet($blockPropertySubset);
		};

		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($blocks, $controllerFactory);
	}
	
	/**
	 * @param Set\RequestSet $requestSet
	 */
	protected function prepareBlockControllers(Set\RequestSet $requestSet)
	{
		$page = $requestSet->getPage();
		$blocks = $requestSet->getBlockSet();
		
		$request = $this->getRequest();

		// function which adds controllers for the block
		$prepare = function(Entity\Abstraction\Block $block) use ($page, $request) {
			$blockController = $block->getController();
			$blockController->setPage($page);
			$blockResponse = $blockController->createResponse($request);
			$blockController->prepare($request, $blockResponse);
			
			return $block;
		};

		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($blocks, $prepare);
	}

	/**
	 * @param Set\RequestSet $requestSet
	 */
	protected function outputBlockControllers(Set\RequestSet $requestSet)
	{
		$blocks = $requestSet->getBlockSet();
		
		// function which adds controllers for the block
		$prepare = function(Entity\Abstraction\Block $block) {
			$blockController = $block->getController();
			$blockController->execute();
			return $block;
		};
		
		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($blocks, $prepare);
	}
	
	/**
	 * Creates place holder response object
	 * @param Entity\Abstraction\Page $page
	 * @param Entity\Abstraction\PlaceHolder $placeHolder
	 * @return PlaceHolderResponse\Response
	 */
	public function createPlaceResponse(Entity\Abstraction\Page $page, Entity\Abstraction\PlaceHolder $placeHolder)
	{
		$response = null;
		
		// TODO: create edit response for unlocked place holders ONLY
		if ($this->request instanceof namespace\Request\RequestEdit) {
			$response = new PlaceHolderResponse\ResponseEdit();
		} else {
			$response = new PlaceHolderResponse\ResponseView();
		}
		
		$response->setPlaceHolder($placeHolder);
		
		return $response;
	}

	/**
	 * Iterates through blocks and returs array of place holder responses
	 * @param array $blocks
	 * @return array
	 */
	protected function getPlaceResponses(Set\RequestSet $requestSet)
	{
		$placeHolders = $requestSet->getPlaceHolderSet();
		$blocks = $requestSet->getBlockSet();
		$page = $requestSet->getPage();
		
		$finalPlaceHolders = $placeHolders->getFinalPlaceHolders();
		
		$placeResponses = array();
		$controller = $this;

		$collectResponses = function(Entity\Abstraction\Block $block, $placeName) 
				use (&$placeResponses, $controller, &$page, $finalPlaceHolders) {
			
			$response = $block->getController()->getResponse();
			
			if ( ! isset($placeResponses[$placeName])) {
				
				if ( ! isset($finalPlaceHolders[$placeName])) {
					//TODO: what is the action on such case?
					throw new Exception\LogicException("Logic problem – final place holder by name $placeName is not found");
				}
				
				// Get place holder object
				$placeHolder = $finalPlaceHolders[$placeName];
				
				$placeResponse = $controller->createPlaceResponse($page, $placeHolder);
				
				
//				if ($page->isPlaceHolderEditable($placeHolder)) {
//					$placeResponse->setPlaceHolder($placeHolder);
//				}
				
				$placeResponses[$placeName] = $placeResponse;
			}
			
			$response->flushToResponse($placeResponses[$placeName]);
		};

		// Iterates through all blocks and collects placeholder responses
		$this->iterateBlocks($blocks, $collectResponses);

		return $placeResponses;
	}

	/**
	 * Iteration funciton for specific array of blocks
	 * @param array $blocks
	 * @param \Closure $function
	 */
	protected function iterateBlocks(Set\BlockSet $blocks, \Closure $function)
	{
		/* @var $block Entity\Abstraction\Block */
		foreach ($blocks as $index => $block) {
			
			$placeHolderName = $block->getPlaceHolder()
					->getName();
			
			try {
				$result = $function($block, $placeHolderName);
			} catch (Exception\InvalidBlockException $e) {
				\Log::swarn("Skipping block $block because of raised SkipBlockException: {$e->getMessage()}");
				unset($blocks[$index]);
			}
		}
	}
	
}