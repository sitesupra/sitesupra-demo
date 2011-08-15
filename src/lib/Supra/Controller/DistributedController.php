<?php

namespace Supra\Controller;

use Supra\Request;
use Supra\Response;

/**
 * Simple HTTP controller based on subcontrollers
 */
abstract class DistributedController extends ControllerAbstraction
{
	/**
	 * Suffix to append to action classes
	 */
	const ACTION_CLASS_SUFFIX = 'Action';
	
	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected static $defaultAction = 'Index';
	
	/**
	 * Must provide the base namespace
	 * @return string
	 */
	abstract function getBaseNamespace();

	/**
	 * Executes the controller
	 */
	public function execute()
	{
		//FIXME: make it work for CLI request as well
		$request = $this->getRequest();
		
		if ( ! $request instanceof Request\HttpRequest) {
			throw new Exception\NotImplementedException("Not http requests are not supported yet");
		}
			
		$action = $request->getActions(1);

		\Log::debug('Action: ', $action);
		$baseAction = static::$defaultAction;

		if ( ! empty($action)) {
			$baseAction = $action[0];
			$request->getPath()->setBasePath(new \Supra\Uri\Path($baseAction));
		}
		
		// Finding class NAMESPACE\AbcDef\AbcDefAction
		$baseNamespace = $this->getBaseNamespace();
		$class = $this->getClassName($baseNamespace, $baseAction);

		\Log::debug('Class: ', $class);

		if ( ! class_exists($class)) {
			throw new Exception\ResourceNotFoundException("Action '$baseAction' was not found (class '$class')");
		}
		
		$actionController = new $class();
		
		if ( ! $actionController instanceof ControllerInterface) {
			throw new Exception\RuntimeException("Action $class must be instance of controller interface");
		}
		
		$response = $actionController->createResponse($request);
		$actionController->prepare($request, $response);
		$actionController->execute();
		
		$response->flushToResponse($this->getResponse());
	}
	
	/**
	 * @param string $url
	 * @return string
	 */
	protected function normalizeUrl($url)
	{
		$url = explode('-', $url);
		$url = array_map('mb_strtolower', $url);
		$url = array_map('ucfirst', $url);
		$url = implode($url);
		
		return $url;
	}
	
	/**
	 * @param string $namespace
	 * @param string $action
	 * @return string 
	 */
	protected function getClassName($namespace, $action)
	{
		// Normalize abc-DEF to class AbcDef so the request remains case insensitive
		$action = $this->normalizeUrl($action);
		
		$class = $namespace . '\\' . $action . '\\' . $action 
				. static::ACTION_CLASS_SUFFIX;
		
		return $class;
	}

	/**
	 * Generate response object
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		if ($request instanceof Request\HttpRequest) {
			return new Response\HttpResponse();
		}
		if ($request instanceof Request\CliRequest) {
			return new Response\CliResponse();
		}
		return new Response\EmptyResponse();
	}
	
}