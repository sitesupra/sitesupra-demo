<?php

namespace Supra\Controller;

use Supra\Request;
use Supra\Response;

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