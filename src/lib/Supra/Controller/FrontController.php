<?php

namespace Supra\Controller;

use Supra\Request;
use Supra\Controller\Exception;
use Supra\Response;
use Supra\Router;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Log\Writer\WriterAbstraction;

/**
 * Front controller
 */
class FrontController
{

	/**
	 * Singleton instance
	 * @var FrontController
	 */
	private static $instance;

	/**
	 * @var WriterAbstraction
	 */
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
		if (isset(self::$instance)) {
			throw new Exception\RuntimeException("Front controller constructor has been run twice");
		}

		$this->log = ObjectRepository::getLogger($this);
		self::$instance = $this;
	}

	/**
	 * Singleton method
	 * @return FrontController
	 */
	public static function getInstance()
	{
		if ( ! isset(self::$instance)) {
			self::$instance = new self();
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
	 * Compare two routers.
	 * Used to sort the routers by depth starting from the deapest level.
	 * @param RouterInterface $a
	 * @param RouterInterface $b
	 * @return integer
	 */
	protected function compareRouters(Router\RouterInterface $a, Router\RouterInterface $b)
	{
		$diff = 0;
		$aPriority = $a->getPriority();
		$bPriority = $b->getPriority();

		if ($bPriority > $aPriority) {
			$diff = 1;
		} elseif ($bPriority < $aPriority) {
			$diff = -1;
		}

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
			$controllers = $this->findControllers($request);

			$break = false;
			foreach ($controllers as $controller) {

				try {
					$this->runController($controller, $request);
				} catch (Exception\StopRequestException $exc) {
					$break = true;
				}
				$controller->output();

				if ($break) {
					break;
				}
			}
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
	public function findControllers(Request\RequestInterface $request)
	{
		$routers = array();
		$controllers = array();
		$allRouters = $this->getRouters();
		foreach ($allRouters as $router) {
			/* @var $router Router\RouterAbstraction */
			if ($router->match($request)) {
				$controller = $router->getController();

				$routers[] = $router;
				$controllers[] = $controller;

				if ( ! $controller instanceof PreFilterInterface) {
					break;
				}
			}
		}

		foreach ($routers as $router) {
			$router->finalizeRequest($request);
		}

		if (empty($controllers)) {
			throw new Exception\ResourceNotFoundException('No controller has been found for the request');
		}

		return $controllers;
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

		$request->readEnvironment();
		
		return $request;
	}

}
