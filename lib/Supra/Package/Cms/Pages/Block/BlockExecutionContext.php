<?php

namespace Supra\Package\Cms\Pages\Block;

use Symfony\Component\HttpFoundation\Request;
use Supra\Package\Cms\Pages\BlockController;

/**
 * Block controller execution context.
 */
class BlockExecutionContext
{
	/**
	 * @var BlockController
	 */
	public $controller;

	/**
	 * @var Request
	 */
	public $request;

	/**
	 * @param BlockController $controller
	 * @param Request $request
	 */
	public function __construct(BlockController $controller, Request $request)
	{
		$this->controller = $controller;
		$this->request = $request;
	}
}