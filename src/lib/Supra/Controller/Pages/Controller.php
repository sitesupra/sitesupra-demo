<?php

namespace Supra\Controller\Pages;

use Supra\Controller\ControllerAbstraction;
use Supra\Response;
use Supra\Response\ResponseInterface;
use Supra\Request\RequestInterface;
use Supra\Controller\Layout;
use Supra\Database\Doctrine;
use Supra\Locale\Data as LocaleData;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query\Expr;
use Supra\Controller\NotFoundException;
use Supra\Controller\Pages\Request\HttpEditRequest;
use Supra\Controller\Pages\Response\PlaceHolder as PlaceHolderResponse;

/**
 * Page controller
 */
class Controller extends ControllerAbstraction
{
	/**
	 * List of block controllers
	 * @var array
	 */
	private $blockControllers = array();
	
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
	 */
	public function execute()
	{
		$request = $this->getRequest();
		
		$blocks = $request->getBlockSet();
		$layout = $request->getLayout();
		$page = $request->getPage();
		
		$places = $request->getPlaceHolderSet();

		$this->findBlockControllers($request);
		\Log::sdebug("Block controllers found for {$page}");
		
		$this->prepareBlockControllers($request);
		\Log::sdebug("Blocks prepared for {$page}");

		$this->executeBlockControllers($request);
		\Log::sdebug("Blocks executed for {$page}");

		$placeResponses = $this->getPlaceResponses($request);

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
		$processor = new Layout\Processor\HtmlProcessor();
		$processor->setLayoutDir(\SUPRA_PATH . 'template');
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
	 * Create block controllers
	 */
	protected function findBlockControllers()
	{
		// function which adds controllers for the block
		$controllerFactory = function(Entity\Abstraction\Block $block) {
			$blockController = $block->createController();
			
			if (empty($blockController)) {
				throw new Exception\InvalidBlockException('Block controller was not found');
			}
			
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
		
		// function which adds controllers for the block
		$prepare = function(Entity\Abstraction\Block $block, BlockController $blockController) use ($request) {
			$block->prepareController($blockController, $request);
		};

		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($prepare);
	}

	/**
	 * Execute block controllers
	 */
	protected function executeBlockControllers()
	{
		// function which adds controllers for the block
		$prepare = function(Entity\Abstraction\Block $block, BlockController $blockController) {
			$blockController->execute();
		};
		
		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($prepare);
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
		
		if ($this->request instanceof namespace\Request\RequestEdit) {
			$response = new PlaceHolderResponse\PlaceHolderResponseEdit();
		} else {
			$response = new PlaceHolderResponse\PlaceHolderResponseView();
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
		
		$placeHolders = $request->getPlaceHolderSet();
		$page = $request->getPage();
		
		$finalPlaceHolders = $placeHolders->getFinalPlaceHolders();
		
		foreach ($finalPlaceHolders as $name => $placeHolder) {
			$placeResponses[$name] = $this->createPlaceResponse($page, $placeHolder);
		}
		
		$collectResponses = function(Entity\Abstraction\Block $block, BlockController $blockController) 
				use (&$placeResponses, &$page, $finalPlaceHolders) {
			
			$response = $blockController->getResponse();
			
			$placeName = $block->getPlaceHolder()
					->getName();
			
			if ( ! isset($placeResponses[$placeName])) {
				
				//TODO: what is the action on such case?
				throw new Exception\LogicException("Logic problem – final place holder by name $placeName is not found");
			}
			
			$response->flushToResponse($placeResponses[$placeName]);
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
				\Log::swarn("Skipping block $block because of raised SkipBlockException: {$e->getMessage()}");
				unset($blocks[$index]);
			}
		}
		
		return $return;
	}
	
}