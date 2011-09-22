<?php

namespace Supra\Controller;

use Supra\Request;
use Supra\Response;

/**
 * Simple HTTP controller based on controller methods in form [method]Action()
 */
abstract class SimpleController extends ControllerAbstraction
{
	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected $defaultAction = 'index';
	
	/**
	 * Action to use if no appropriate action is found
	 * @var string
	 */
	protected $notFoundAction = null;

	/**
	 * Executes the controller
	 */
	public function execute()
	{
		$request = $this->getRequest();
		$actions = $request->getActions(null);

		\Log::debug('Actions: ', $actions);

		if (empty($actions)) {
			$actions = array($this->defaultAction);
		} else {
			
			// Normalize name
			foreach ($actions as &$action) {
				// Ignore extension, @TODO: could implement this in some other level
				// Removes extension
				$action = preg_replace('/\..*/', '', $action);
				$action = explode('-', $action);
				$action = array_map('mb_strtolower', $action);
				$action = array_map('ucfirst', $action);
				$action = implode($action);
			}
			unset($action);
		}

		$method = $this->getMethodName($actions);

		\Log::debug('Method: ', $method);

		$methods = get_class_methods($this);
		
		// TODO: do case sensitive method name search
		if ( ! in_array($method, $methods)) {
			
			// If not found action set, call it by passing all original actions
			if ( ! empty($this->notFoundAction)) {
				$notFoundMethod = $this->getMethodName($this->notFoundAction);
				
				if (in_array($notFoundMethod, $methods)) {
					$this->$notFoundMethod($actions);
					
					return;
				}
			}
			
			$className = get_class($this);
			throw new Exception\ResourceNotFoundException("Method '{$method}' doesn't exist for class '{$className}'");
		}
		
		$this->$method();
	}
	
	private function getMethodName($actions)
	{
		$actions = (array) $actions;
		$method = implode('', $actions) . 'Action';
		$method = lcfirst($method);
		
		return $method;
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