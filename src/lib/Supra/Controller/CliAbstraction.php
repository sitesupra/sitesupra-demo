<?php

namespace Supra\Controller;

use Supra\Request;
use Supra\Response;

/**
 * Cli abstract controller
 */
abstract class CliAbstraction extends ControllerAbstraction
{
	/**
	 * Generate response object
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		$response = new Response\CliResponse();
		return $response;
	}
}