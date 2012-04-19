<?php

namespace Supra\Controller;

use Supra\Request;
use Supra\Response;

/**
 * Controller with no response and logics
 */
class EmptyController extends ControllerAbstraction
{
	/**
	 * Get response object
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		return new Response\EmptyResponse();
	}
	
	/**
	 * Empty execute method
	 */
	public function execute()
	{
		
	}
}