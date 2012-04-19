<?php

namespace Supra\Controller\Event;

use Supra\Event\EventArgs;

class FrontControllerShutdownEventArgs extends EventArgs
{
	const frontControllerShutdownEvent = 'frontControllerShutdownEvent';
	
	public $frontController;
}
