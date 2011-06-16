<?php

namespace Supra\Controller;

use Supra\Request,
		Supra\Response,
		Supra\Router;

/**
 * Front controller
 */
class FrontController
{
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
		$diff = $aPriority - $bPriority;
		return ( - $diff);
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
		$controller = $this->findController($request);
		$response = $controller->createResponse($request);
		$response->prepare();
		$controller->prepare($request, $response);
		$controller->execute();
		$controller->output();
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
		throw new NotFoundException('No controller has been found for the request');
	}

	/**
	 * Creates request instance
	 * @return Request\RequestInterface
	 */
	protected function getRequestObject()
	{
		$request = null;
		
		if ( ! isset($_SERVER['SERVER_NAME'])) {
			$request = new Request\Cli();
		} else {
			$request = new Request\Http();
		}
		
		return $request;
	}

}
