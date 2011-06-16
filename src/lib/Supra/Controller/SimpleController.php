<?php

namespace Supra\Controller;

use Supra\Request,
		Supra\Response;

/**
 * Simple HTTP controller based on controller methods in form [method]Action()
 */
abstract class SimpleController extends ControllerAbstraction
{
	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected static $defaultAction = 'index';

	/**
	 * Executes the controller
	 */
	public function execute()
	{
		$request = $this->getRequest();
		$actions = $request->getActions(null);

		\Log::sdebug('Actions: ', $actions);

		if (empty($actions)) {
			$actions = array(static::$defaultAction);
		} else {
			
			// Normalize name
			foreach ($actions as &$action) {
				$action = explode('-', $action);
				$action = array_map('mb_strtolower', $action);
				$action = array_map('ucfirst', $action);
				$action = implode($action);
			}
			unset($action);
		}

		$method = implode('', $actions) . 'Action';
		$method = lcfirst($method);

		\Log::sdebug('Method: ', $method);

		$methods = get_class_methods($this);
		
		// TODO: do case sensitive method name search
		if ( ! in_array($method, $methods)) {
			throw new NotFoundException();
		}
		
		$this->$method();
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