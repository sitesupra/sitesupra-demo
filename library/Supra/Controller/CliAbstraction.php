<?php

namespace Supra\Controller;

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
	public function getResponseObject(Request\RequestInterface $request)
	{
		$response = new Response\Cli();
		return $response;
	}
}