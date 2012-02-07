<?php

namespace Supra\Controller\Pages;

use Supra\Request\RequestInterface;
use Supra\Response\ResponseInterface;

/**
 * CachedBlockController
 */
class CachedBlockController extends BlockController
{
	public function __construct(ResponseInterface $response)
	{
		parent::__construct();
		$this->response = $response;
	}
	
	public function execute()
	{
		
	}
	
	public function createResponse(RequestInterface $request)
	{
		return $this->response;
	}
	
//	public function setBlockPropertySet()
//	{
//		
//	}
//	
//	public function prepareTwigHelper()
//	{
//		
//	}
}
