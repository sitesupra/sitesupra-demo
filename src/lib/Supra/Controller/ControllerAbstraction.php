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
	 * Execute controller
	 */
	public function execute()
	{}

	/**
	 * Output the result
	 */
	public function output()
	{}

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