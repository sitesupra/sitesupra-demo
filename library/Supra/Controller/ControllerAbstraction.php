<?php

namespace Supra\Controller;

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

	public function execute(Request\RequestInterface $request, Response\ResponseInterface $response)
	{
		$this->request = $request;
		$this->response = $response;
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
}