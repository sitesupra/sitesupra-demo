<?php

namespace Supra\Controller\Pages\Event;

use Supra\Event\EventArgs;

class BlockEventsArgs  extends EventArgs {
	
	public $blockClass;
	public $duration;
}

