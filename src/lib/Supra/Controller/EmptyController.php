<?php

namespace Supra\Controller;

/**
 * Description of Empty
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
}