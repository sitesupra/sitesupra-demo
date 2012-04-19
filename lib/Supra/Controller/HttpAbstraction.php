<?php

namespace Supra\Controller;

use Supra\Request;
use Supra\Response;

/**
 * HTTP abstract controller
 */
abstract class HttpAbstraction extends ControllerAbstraction
{
	/**
	 * Generate response object
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		return new Response\HttpResponse();
	}
}