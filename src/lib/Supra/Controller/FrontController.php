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
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Routing rules
	 * @param Route\RouterInterface $router
	 * @param string $controllerClass
	 */
	public function route(Router\RouterInterface $router, $controllerClass)
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
		}
		elseif ($bPriority < $aPriority) {
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
		if (!$this->routersOrdered) {
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
			$request->readEnvironment();
			$this->findMatchingRouters($request);
		}
		catch (\Exception $exception) {

			// Log the exception raised
			$this->log->error($exception);

			//TODO: should be configurable somehow
			$exceptionControllerClass = 'Supra\Controller\ExceptionController';
			
			$exceptionController = $this->initializeController($exceptionControllerClass);
			/* @var $exceptionController Supra\Controller\ExceptionController */
			$exceptionController->setException($exception);
			$this->runControllerInner($exceptionController, $request);
			$exceptionController->output();
		}
		
		$sessionManagers = ObjectRepository::getAllSessionManagers();
		foreach ($sessionManagers as $manager) {
			$manager->getHandler()->close();
		}
		
	}
	
	/**
	 * Create controller instance
	 * @param string $controllerClass
	 * @return ControllerInterface
	 */
	private function initializeController($controllerClass)
	{
		ObjectRepository::beginControllerContext($controllerClass);
		$controller = Loader::getClassInstance($controllerClass, 'Supra\Controller\ControllerInterface');
		
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
			$objectRepositoryCaller = $router->getObjectRepositoryCaller();
			
			if ( ! empty($objectRepositoryCaller)) {
				ObjectRepository::setCallerParent($controller, $objectRepositoryCaller);
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

				$authenticationNamespace = ObjectRepository::getSessionManager($controller)
						->getAuthenticationSpace();

				if ($authenticationNamespace instanceof AuthenticationSessionNamespace) {

					$user = $authenticationNamespace->getUser();

					if ( ! is_null($user) && $appConfig->authorizationAccessPolicy->isApplicationAnyAccessGranted($user)) {
						$controller->execute();
					}
					else {
						throw new ApplicationAccessDeniedException($user, $appConfig);
					}
				}
				else {
					throw new Exception\RuntimeException('Could not get authentication session namespace.');
				}
			}
			else {
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
				$controller = $this->initializeController($controllerClass);

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
