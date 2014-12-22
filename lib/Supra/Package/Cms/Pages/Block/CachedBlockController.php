<?php

namespace Supra\Package\Cms\Pages\Block;

use Supra\Package\Cms\Entity\Abstraction\Block;
use Symfony\Component\HttpFoundation\Request;
use Supra\Package\Cms\Pages\BlockController;
use Supra\Package\Cms\Pages\Response\ResponsePart;

/**
 * CachedBlockController
 */
class CachedBlockController extends BlockController
{
	/**
	 * @param ResponsePart $cachedResponse
	 */
	public function __construct(ResponsePart $cachedResponse, Block $block)
	{
		$this->response = $cachedResponse;
		$this->block = $block;
	}

	/**
	 * @throws \LogicException
	 */
	final public function doExecute()
	{
		throw new \LogicException('Cached block controller should not be executed.');
	}

	/**
	 * @param Request $request
	 * @return ResponsePart
	 */
	public function createBlockResponse(Request $request)
	{
		return $this->response;
	}
}
