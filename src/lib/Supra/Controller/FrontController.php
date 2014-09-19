<?php

namespace Supra\Controller;

use Supra\Core\Event\KernelEvent;
use Supra\Core\Event\RequestResponseEvent;
use Supra\Request;
use Supra\Controller\Exception;
use Supra\Router;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Log\Writer\WriterAbstraction;
use Supra\Authorization\Exception\ApplicationAccessDeniedException;
use Supra\Cms\ApplicationConfiguration;
use Supra\Authorization\AccessPolicy\AuthorizationAccessPolicyAbstraction;
use Supra\Loader\Loader;
use Closure;
use Supra\Controller\Event\FrontControllerShutdownEventArgs;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

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
	 * @var string
	 */
	protected $exceptionControllerClass;

		/**
		 * A DI container as it is
		 *
		 * @var \Supra\DependencyInjection\Container
		 */
		protected $container;

	/**
	 * Binds the log writer
	 */
	public function __construct()
	{
		if (isset(self::$instance)) {
			throw new Exception\RuntimeException("Front controller constructor has been run twice");
		}

				//debugging
				\Symfony\Component\Debug\Debug::enable(-1, true);

				$application = new \SupraApplication();

				$this->container = $application->buildContainer();
				$this->container['kernel'] = $this;

				//HttpFoundation and initialization stuff should happen here
				$this->log = ObjectRepository::getLogger($this);
				self::$instance = $this;

				$application->boot();
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
	 * @param string $class
	 */
	public function setExceptionControllerClass($class)
	{
		$this->exceptionControllerClass = $class;
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

	public function parseControllerName($name)
	{
		//this should be more bulletproof
		list($package, $controller, $action) = explode(':', $name);

		$packageName = $this->container->getApplication()->resolvePackage($package);

		$parts = explode('\\', $packageName);

		array_pop($parts);

		$namespace = implode('\\', $parts);

		return array(
			'controller' => '\\'.$namespace.'\\Controller\\'.$controller.'Controller',
			'action' => $action
		);
	}

	/**
	 * Execute the front controller
	 */
	public function execute()
	{
		$request = $this->container->getRequest();
		//new way
		try {

			$requestEvent = new RequestResponseEvent();
			$requestEvent->setRequest($request);

			$this->container->getEventDispatcher()->dispatch(KernelEvent::REQUEST, $requestEvent);
			//here event can be overridden by any listener, so check if we have event

			if ($requestEvent->hasResponse()) {
				$response = $requestEvent->getResponse();
				$response->send();
				return;
			}

			$router = $this->container->getRouter();
			$configuration = $router->match($request);

			//@todo: recall correctly how symfony deals with that
			$request->attributes = new ParameterBag($configuration);

			//@todo: do not execute controller that ugly
			$controllerDefinition = $this->parseControllerName($configuration['controller']);

			//probably there should be a better implementation of a package setting
			$controllerObject = new $controllerDefinition['controller']();
			$controllerObject->setContainer($this->container);

			$action = $controllerDefinition['action'].'Action';

			//todo: here we should fire 2 events: generic http.response and controller.response before that
			$response = $controllerObject->$action($request);

			$responseEvent = new RequestResponseEvent();
			$responseEvent->setRequest($request);
			$responseEvent->setResponse($response);

			$this->container->getEventDispatcher()->dispatch(KernelEvent::RESPONSE, $responseEvent);

			if (!$response instanceof Response) {
				throw new \Exception('Response returned by your controller is not an instance of HttpFoundation\Response');
			}
			$response->send();
			return;
		} catch(ResourceNotFoundException $e) {
			$notFoundEvent = new RequestResponseEvent();
			$notFoundEvent->setRequest($request);
			$this->container->getEventDispatcher()->dispatch(KernelEvent::ERROR404, $notFoundEvent);

			if($notFoundEvent->hasResponse()) {
				$notFoundEvent->getResponse()->send();
				return;
			}
		}

		//old way
		$request = $this->getRequestObject();
		try {
			
			$request->readEnvironment();
			$this->findMatchingRouters($request);
		} catch (\Exception $exception) {
						if (!$exception instanceof Exception\ResourceNotFoundException && true) { //@todo: debug/release mode here
							throw $exception;
						} else {
							// Log anything except ResourceNotFoundException
							if ( ! $exception instanceof Exception\ResourceNotFoundException) {
									$exceptionIdentifier = md5((string) $exception);
									$this->log->error('#' . $exceptionIdentifier, ' ', $exception, "\nrequest: ", $request->getRequestMethod() . ' ' . $request->getActionString());
							}

							if ($this->exceptionControllerClass !== null) {
									$exceptionController = $this->initializeController($this->exceptionControllerClass);
							} else {
									$exceptionController = $this->initializeController(DefaultExceptionController::CN());
							}
							/* @var $exceptionController Supra\Controller\ExceptionController */

							$exceptionController->setException($exception);
							$this->runControllerInner($exceptionController, $request);
							$exceptionController->output();
						}
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

				$userProvider = ObjectRepository::getUserProvider($controller);
				$user = $userProvider->getSignedInUser(false);

				if ( ! is_null($user) && $appConfig->authorizationAccessPolicy->isApplicationAnyAccessGranted($user)) {
					$controller->execute();
				} else {
					throw new ApplicationAccessDeniedException($user, $appConfig);
				}
			} else {
				$controller->execute();
			}
		} catch (\Exception $unhandledException) {
			try {
				$controller->handleException($unhandledException);
			} catch (\Exception $uncaughtException) {
				// will throw after finalizing the execution
			}
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
