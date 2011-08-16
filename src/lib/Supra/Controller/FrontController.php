<?php

namespace Supra\Controller;

use Supra\Request;
use Supra\Response;
use Supra\Router;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Front controller
 */
class FrontController
{
	private $log;
	
	/**
	 * Routing array
	 * @var Router\RouterInterface[]
	 */
	protected $routers = array();

	/**
	 * Whether routers are ordered by priority already
	 * @var boolean
	 */
	protected $routersOrdered = true;

	/**
	 * Binds the log writer
	 */
	public function __construct()
	{
		$this->log = ObjectRepository::getLogger($this);
	}
	
	/**
	 * Routing rules
	 * @param Route\RouterInterface $router
	 * @param string $controllerClass
	 */
	function route(Router\RouterInterface $router, $controllerClass)
	{
		$router->setControllerClass($controllerClass);
		$this->routers[] = $router;
		$this->routersOrdered = false;
	}

	/**
	 * Compare two routers.
	 * Used to sort the routers by depth starting from the deapest level.
	 * @param RouterInterface $a
	 * @param RouterInterface $b
	 * @return integer
	 */
	protected function compareRouters(Router\RouterInterface $a, Router\RouterInterface $b)
	{
		$aPriority = $a->getPriority();
		$bPriority = $b->getPriority();
		$diff = $bPriority - $aPriority;
		
		return $diff;
	}

	/**
	 * Return array of bound router-controller pairs
	 * @return Router\RouterInterface[]
	 */
	protected function getRouters()
	{
		if ( ! $this->routersOrdered) {
			usort($this->routers, array($this, 'compareRouters'));
			$this->routersOrdered = true;
		}
		return $this->routers;
	}

	/**
	 * Execute the front controller
	 */
	public function execute()
	{
		$request = $this->getRequestObject();
		
		try {
			$controller = $this->findController($request);

			$this->runController($controller, $request);

			$controller->output();
		} catch (\Exception $exception) {

			// Log the exception raised
			$this->log->error($exception);
			
			$exceptionController = $this->findExceptionController($request, $exception);
			$this->runController($exceptionController, $request);
			$exceptionController->output();
		}
	}
	
	/**
	 * Runs controller
	 * @param ControllerInterface $controller
	 * @param Request\RequestInterface $request
	 */
	public function runController(ControllerInterface $controller, Request\RequestInterface $request)
	{
		$response = $controller->createResponse($request);
		$response->prepare();
		$controller->prepare($request, $response);
		$controller->execute();
	}

	/**
	 * Find matching controller by the request
	 * @param Request\RequestInterface $request
	 * @return ControllerInterface
	 */
	public function findController(Request\RequestInterface $request)
	{
		foreach ($this->getRouters() as $router) {
			/* @var $router Router\RouterAbstraction */
			
			if ($router->match($request)) {
				$controller = $router->getController();
				
				return $controller;
			}
		}
		
		throw new Exception\ResourceNotFoundException('No controller has been found for the request');
	}
	
	/**
	 * Get controller to show exception details
	 * @param Request\RequestInterface $request
	 * @param \Exception $exception
	 * @return ControllerInterface
	 * @TODO: some routing and configuration possibilities will be needed, also setting this to empty controller for production
	 */
	public function findExceptionController(Request\RequestInterface $request, \Exception $exception)
	{
		$exceptionController = new ExceptionController();
		$exceptionController->setException($exception);
		
		return $exceptionController;
	}

	/**
	 * Creates request instance
	 * @return Request\RequestInterface
	 */
	protected function getRequestObject()
	{
		$request = null;
		
		if ( ! isset($_SERVER['SERVER_NAME'])) {
			$request = new Request\CliRequest();
		} else {
			$request = new Request\HttpRequest();
		}
		
		return $request;
	}

}
