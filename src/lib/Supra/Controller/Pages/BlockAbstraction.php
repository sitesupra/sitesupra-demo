<?php

namespace Supra\Controller\Pages;

use Supra\Controller\ControllerAbstraction,
		Supra\Controller\Request,
		Supra\Controller\Response;

/**
 * Block object abstraction
 * @MappedSuperclass
 */
abstract class BlockAbstraction extends ControllerAbstraction
{
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var integer
	 */
	protected $id;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

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