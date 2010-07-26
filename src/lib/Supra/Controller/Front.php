<?php

namespace Supra\Controller;

/**
 * Front controller
 */
class Front
{
	/**
	 * Singleton instance
	 * @var Front
	 */
	static protected $instance;

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
	 * Return the front controller instance
	 * @return Front
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
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
	 * Compare two routers. Used to sort the routers by
	 * @param RouterInterface $a
	 * @param RouterInterface $b
	 * @return integer
	 */
	protected function compareRouters(Router\RouterInterface $a, Router\RouterInterface $b)
	{
		$aPriority = $a->getPriority();
		$bPriority = $b->getPriority();
		if ($aPriority == $bPriority) {
			return 0;
		}
		if ($aPriority > $bPriority) {
			return 1;
		}
		return -1;
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
		$response = $controller->getResponseObject($request);
		$response->prepare();
		$controller->prepare($request, $response);
		$controller->execute();
		$controller->output();
		$response->flush();
	}

	/**
	 * Find matching controller by the request
	 * @param Request\RequestInterface $request
	 * @return ControllerInterface
	 */
	public function findController($request)
	{
		foreach ($this->getRouters() as $router) {
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
		if ( ! isset($_SERVER['SERVER_NAME'])) {
			$request = new Request\Cli();
		} else {
			$request = new Request\Http();
		}
		return $request;
	}

}