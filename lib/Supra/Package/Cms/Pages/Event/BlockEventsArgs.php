<?php

namespace Supra\Package\Cms\Pages\Event;

use Supra\Event\EventArgs;
use Supra\Controller\Pages\BlockController;

class BlockEventsArgs extends EventArgs
{
	/**
	 * @var float
	 */
	public $duration;

	/**
	 * @var \Supra\Controller\Pages\Entity\Abstraction\Block
	 */
	public $block;

	/**
	 * @var BlockController
	 */
	public $blockController;

	/**
	 * @var string
	 */
	public $actionType;

	/**
	 * @var boolean
	 */
	public $cached;

	/**
	 * @var \RuntimeException
	 */
	public $exception;

	/**
	 * @var boolean
	 */
	public $blockRequest = false;
	
	/**
	 * @var Supra\Controller\Pages\Request\PageRequest
	 */
	public $request;
	
	/**
	 * @var Supra\Response\HttpResponse
	 */
	public $response;
}
