<?php

namespace Supra\Controller;

use Supra\Request;
use Supra\Response;

/**
 * Controller interface
 */
interface ControllerInterface
{
	/**
	 * Prepares the controller
	 * @param Request\RequestInterface $request
	 * @param Response\ResponseInterface $response
	 */
	public function prepare(Request\RequestInterface $request, Response\ResponseInterface $response);

	/**
	 * Executes the controller
	 */
	public function execute();

	/**
	 * Outputs the result
	 */
	public function output();

	/**
	 * Generate response object
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface
	 */
	public function createResponse(Request\RequestInterface $request);

	/**
	 * Handles controller raised exception
	 * @param \Exception $e
	 */
	public function handleException(\Exception $e);
}