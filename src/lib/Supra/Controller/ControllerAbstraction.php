<?php

namespace Supra\Controller;

use Supra\Request;
use Supra\Response;

/**
 * Controller abstraction class
 */
abstract class ControllerAbstraction implements ControllerInterface
{
	/**
	 * Request object
	 * @var Request\RequestInterface
	 */
	protected $request;

	/**
	 * Response object
	 * @var Response\ResponseInterface
	 */
	protected $response;

	/**
	 * Prepares controller for execution
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	public function prepare(Request\RequestInterface $request, Response\ResponseInterface $response)
	{
		$this->request = $request;
		$this->response = $response;
	}

	/**
	 * Output the result
	 */
	public function output()
	{
		$response = $this->getResponse();
		$response->flush();
	}

	/**
	 * Get request object
	 * @return Request\RequestInterface
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Get response object
	 * @return Response\ResponseInterface
	 */
	public function getResponse()
	{
		return $this->response;
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