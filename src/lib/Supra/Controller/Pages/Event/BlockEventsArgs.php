<?php

namespace Supra\Controller\Pages\Event;

use Supra\Event\EventArgs;

class BlockEventsArgs  extends EventArgs {
	
	/**
	 * @var float
	 */
	public $duration;
	
	/**
	 * @var Supra\Controller\Pages\Entity\Abstraction\Block
	 */
	public $block;
	
	/**
	 * @var string
	 */
	public $actionType;
}

