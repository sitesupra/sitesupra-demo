<?php

namespace Supra\Controller\Pages;

use Supra\Controller\ControllerAbstraction,
		Supra\Controller\Request,
		Supra\Controller\Response;

/**
 * Block controller abstraction
 */
abstract class BlockController extends ControllerAbstraction
{
	/**
	 * Output
	 */
	public function output()
	{
		//TODO: use smarty view
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