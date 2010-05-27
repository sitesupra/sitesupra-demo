<?php

namespace Supra\Controller\Pages;

use Supra\Controller\ControllerAbstraction;
use Supra\Controller\Response;
use Supra\Controller\Request;

/**
 * Page controller
 */
class Controller extends ControllerAbstraction
{
	/**
	 * Execute controller
	 * @param RequestInterface $request
	 * @param ResponseInterface $response 
	 */
	public function execute(Request\RequestInterface $request, Response\ResponseInterface $response)
	{
		parent::__execute($request, $response);
		
	}

	/**
	 * Generate response object
	 * @param Request\RequestInterface
	 * @return Response\Http
	 */
	public function getResponseObject(Request\RequestInterface $request)
	{
		return new Response\Http();
	}
}