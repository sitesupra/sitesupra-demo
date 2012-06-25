<?php

namespace Supra\Controller;

use Supra\Request;
use Supra\Controller\Exception;
use Supra\Response;
use Supra\Router;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Log\Writer\WriterAbstraction;
use Supra\Authorization\AuthorizedControllerInterface;
use Supra\Authorization\Exception\ApplicationAccessDeniedException;
use Supra\Cms\ApplicationConfiguration;
use Supra\Authentication\AuthenticationSessionNamespace;
use Supra\Authorization\AccessPolicy\AuthorizationAccessPolicyAbstraction;
use Supra\Loader\Loader;
use Closure;
use Supra\Controller\Event\FrontControllerShutdownEventArgs;

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
	 * @param Closure $controllerClosure
	 */
	public function route(Router\RouterInterface $router, $controllerClass, Closure $controllerClosure = null)
	{
		$router->setControllerClass($controllerClass);
		$router->setControllerClosure($controllerClosure);
		$this->routers[] = $router;
		$this->routersOrdered = false;
	}

	/**
	 * Return by priority ordered array of routers
	 * @return Router\RouterInterface[]
	 */
	protected function getRouters()
	{
		if ( ! $this->routersOrdered) {
			$routerPriorities = array();
			$routerOrder = array();
			$orderKey = 0;

			// Generate arrays to order routers by priority and then by order 
			// how they were added initially
			foreach ($this->routers as $router) {
				/* @var $router Router\RouterInterface  */
				$routerPriorities[] = $router->getPriority();
				$routerOrder[] = $orderKey ++;
			}

			array_multisort($routerPriorities, SORT_DESC, SORT_REGULAR, $routerOrder, SORT_ASC, SORT_NUMERIC, $this->routers);

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

			$request->readEnvironment();
			$this->findMatchingRouters($request);
		} catch (\Exception $exception) {

			// Log the exception raised
			$exceptionIdentifier = md5($exception);
			$this->log->error('#' . $exceptionIdentifier, ' ', $exception);

			//TODO: should be configurable somehow
			$exceptionController = $this->initializeController(ExceptionController::CN());
			/* @var $exceptionController Supra\Controller\ExceptionController */
			$exceptionController->setException($exception);
			$this->runControllerInner($exceptionController, $request);
			$exceptionController->output();
		}

		$eventManager = ObjectRepository::getEventManager();

		$shutdownEventArgs = new FrontControllerShutdownEventArgs();
		$shutdownEventArgs->frontController = $this;

		$eventManager->fire(FrontControllerShutdownEventArgs::frontControllerShutdownEvent, $shutdownEventArgs);
	}

	/**
	 * Create controller instance
	 * @param string $controllerClass
	 * @param Closure $controllerClosure
	 * @return ControllerInterface
	 */
	private function initializeController($controllerClass, Closure $controllerClosure = null)
	{
		if ( ! $controllerClosure instanceof Closure) {
			$controllerClosure = function() use ($controllerClass) {
						return Loader::getClassInstance($controllerClass, 'Supra\Controller\ControllerInterface');
					};
		}

		ObjectRepository::beginControllerContext($controllerClass);
		$controller = $controllerClosure();

		if (get_class($controller) != $controllerClass) {
			$this->log->warn("Controller classname $controllerClass doesn't match with object classname initialized");
		}

		if ( ! $controller instanceof ControllerInterface) {
			throw new Exception\RuntimeException("Controller initialization step failed to generate controller instance using class $controllerClass");
		}

		return $controller;
	}

	/**
	 * Run controller
	 * @param ControllerInterface $controller
	 * @param Request\RequestInterface $request
	 * @param Router\RouterInterface $router
	 */
	private function runControllerInner(ControllerInterface $controller, Request\RequestInterface $request, Router\RouterInterface $router = null)
	{
		if ( ! is_null($router) && ! $controller instanceof PreFilterInterface) {
			$router->finalizeRequest($request);
		}

		if ( ! is_null($router)) {
			ObjectRepository::setCallerParent($controller, $router);
			$objectRepositoryCaller = $router->getObjectRepositoryCaller();

			if ( ! empty($objectRepositoryCaller)) {
				ObjectRepository::setCallerParent($router, $objectRepositoryCaller);
			} else {
				ObjectRepository::setCallerParent($router, get_class($controller));
			}
		}

		$controllerClass = get_class($controller);

		try {

			$response = $controller->createResponse($request);
			$response->prepare();
			$controller->prepare($request, $response);

			$appConfig = ObjectRepository::getApplicationConfiguration($controller);

			if (
					$appConfig instanceof ApplicationConfiguration &&
					$appConfig->authorizationAccessPolicy instanceof AuthorizationAccessPolicyAbstraction
			) {

				$sessionManager = ObjectRepository::getSessionManager($controller);

				$authenticationNamespace = $sessionManager->getAuthenticationSpace();

				if ($authenticationNamespace instanceof AuthenticationSessionNamespace) {

					$user = $authenticationNamespace->getUser();

					if ( ! is_null($user) && $appConfig->authorizationAccessPolicy->isApplicationAnyAccessGranted($user)) {
						$controller->execute();
					} else {
						throw new ApplicationAccessDeniedException($user, $appConfig);
					}
				} else {
					throw new Exception\RuntimeException('Could not get authentication session namespace.');
				}
			} else {
				$controller->execute();
			}
		} catch (\Exception $uncaughtException) {
			
		}

		ObjectRepository::endControllerContext($controllerClass);

		if ( ! empty($uncaughtException)) {
			throw $uncaughtException;
		}
	}

	/**
	 * Runs controller
	 * @param string $controllerClass
	 * @param Request\RequestInterface $request
	 * @param Router\RouterInterface $router
	 * @return ControllerInterface
	 */
	public function runController($controllerClass, Request\RequestInterface $request)
	{
		$controller = $this->initializeController($controllerClass);
		$this->runControllerInner($controller, $request);

		return $controller;
	}

	/**
	 * Find matching controller by the request
	 * @param Request\RequestInterface $request
	 */
	public function findMatchingRouters(Request\RequestInterface $request)
	{
		$allRouters = $this->getRouters();
		$controllerFound = false;
		$stopRequest = false;

		foreach ($allRouters as $router) {
			/* @var $router Router\RouterAbstraction */
			if ($router->match($request)) {

				$controllerClass = $router->getControllerClass();
				$controllerClosure = $router->getControllerClosure();
				$controller = $this->initializeController($controllerClass, $controllerClosure);

				try {
					$this->runControllerInner($controller, $request, $router);
				} catch (Exception\StopRequestException $exc) {
					$stopRequest = true;
				}

				// Stop on matching not prefilter controller
				if ( ! $controller instanceof PreFilterInterface) {
					$stopRequest = true;
				}

				$controller->output();

				if ($stopRequest) {
					$controllerFound = true;
					break;
				}
			}
		}

		if ( ! $controllerFound) {
			throw new Exception\ResourceNotFoundException('No controller has been found for the request');
		}
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
	 * Creates request instance. Only HttpRequest supported now.
	 * @return Request\RequestInterface
	 */
	protected function getRequestObject()
	{
		$request = new Request\HttpRequest();

		return $request;
	}

}
