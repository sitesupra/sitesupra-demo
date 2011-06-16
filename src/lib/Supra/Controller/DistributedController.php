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
		
		// Normalize abc-DEF to class AbcDef so the request remains case insensitive
		$baseAction = explode('-', $baseAction);
		$baseAction = array_map('mb_strtolower', $baseAction);
		$baseAction = array_map('ucfirst', $baseAction);
		$baseAction = implode($baseAction);
		
		// Finding class NAMESPACE\AbcDef\AbcDefAction
		$baseNamespace = $this->getBaseNamespace();
		$class = $baseNamespace . '\\' . $baseAction . '\\' . $baseAction 
				. static::ACTION_CLASS_SUFFIX;

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