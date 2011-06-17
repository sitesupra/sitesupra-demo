<?php

namespace Supra\Controller;

use Supra\Request,
		Supra\Response;

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
		
		if ( ! $request instanceof Request\Http) {
			throw new Exception("Not http requests are not supported yet");
		}
			
		$action = $request->getActions(1);

		\Log::sdebug('Action: ', $action);
		$baseAction = static::$defaultAction;

		if ( ! empty($action)) {
			$baseAction = $action[0];
			$request->getPath()->setBasePath(new \Supra\Uri\Path($baseAction));
		}
		
		// Finding class NAMESPACE\AbcDef\AbcDefAction
		$baseNamespace = $this->getBaseNamespace();
		$class = $this->getClassName($baseNamespace, $baseAction);

		\Log::sdebug('Class: ', $class);

		if ( ! class_exists($class)) {
			throw new NotFoundException();
		}
		
		$actionController = new $class();
		
		if ( ! $actionController instanceof ControllerInterface) {
			throw new Exception("Action $class must be instance of controller interface");
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
		if ($request instanceof Request\Http) {
			return new Response\Http();
		}
		if ($request instanceof Request\Cli) {
			return new Response\Cli();
		}
		return new Response\EmptyResponse();
	}

}