<?php

namespace Supra\Controller\Pages;

use Supra\Controller\ControllerAbstraction;
use Supra\Controller\Request;
use Supra\Controller\Response;

/**
 * Block object absrtaction
 */
abstract class BlockAbsrtaction extends ControllerAbstraction
{
	/**
	 * Execute controller
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	public function execute(Request\RequestInterface $request, Response\ResponseInterface $response)
	{
		parent::execute($request, $response);
		
	}

	/**
	 * Generate response object
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface
	 */
	public function getResponseObject(Request\RequestInterface $request)
	{
		return new Response\Http();
	}
}