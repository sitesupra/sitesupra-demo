<?php

namespace Supra\Package\Cms\Pages\Block;

use Supra\Package\Cms\Pages\BlockController;
use Supra\Package\Cms\Pages\Response\ResponsePart;

/**
 * CachedBlockController
 */
class CachedBlockController extends BlockController
{
	public function __construct(ResponsePart $response)
	{
		$this->response = $response;
	}
	
	public function doExecute()
	{
		
	}
	
	public function createResponse(RequestInterface $request)
	{
		return $this->response;
	}
}
