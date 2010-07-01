<?php

namespace Supra\Controller;

/**
 * Simple HTTP controller based on controller methods in form [method]Action()
 */
abstract class Simple extends ControllerAbstraction
{
	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected static $defaultAction = 'index';

	/**
	 * Executes the controller
	 * @param Request\RequestInterface $request
	 * @param Response\ResponseInterface $response
	 */
	public function execute(Request\RequestInterface $request, Response\ResponseInterface $response)
	{
		parent::execute($request, $response);
		$actions = $request->getActions(null);

		\Log::sdebug('Actions: ', $actions);

		if (empty($actions)) {
			$actions = array(static::$defaultAction);
		} else {
			$first = true;
			foreach ($actions as &$action) {
				if ( ! $first) {
					$action = ucfirst($action);
				}
				$first = false;
			}
			unset($action);
		}

		$method = implode('', $actions) . 'Action';

		\Log::sdebug('Method: ', $method);

		if ( ! method_exists($this, $method)) {
			throw new NotFoundException();
		}
		$this->$method();
	}

	/**
	 * Generate response object
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface
	 */
	public function getResponseObject(Request\RequestInterface $request)
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