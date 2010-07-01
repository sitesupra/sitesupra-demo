<?php

namespace Supra\Controller;

/**
 * Description of Empty
 */
class EmptyController extends ControllerAbstraction
{
	/**
	 * Executes the empty controller
	 * @param Request\RequestInterface $request
	 * @param Response\ResponseInterface $response
	 */
	public function execute(Request\RequestInterface $request, Response\ResponseInterface $response)
	{
		parent::execute($request, $response);
	}

	/**
	 * Get response object
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface
	 */
	public function getResponseObject(Request\RequestInterface $request)
	{
		return new Response\EmptyResponse();
	}
}