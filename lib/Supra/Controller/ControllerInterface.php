<?php

namespace Supra\Controller;

/**
 * Controller interface
 */
interface ControllerInterface
{
	/**
	 * Executes the controller
	 * @param Request\RequestInterface $request
	 * @param Response\ResponseInterface $response
	 */
	public function execute(Request\RequestInterface $request, Response\ResponseInterface $response);

	/**
	 * Generate response object
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface
	 */
	public function getResponseObject(Request\RequestInterface $request);
}