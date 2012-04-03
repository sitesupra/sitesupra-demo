<?php

namespace Supra\Controller\Event;

use Supra\Event\EventArgs;

class FrontControllerShutdownEventArgs extends EventArgs
{
	const FRONTCONTROLLER_SHUTDOWN = 'frontControllerShutdownEvent';
	
	public $frontController;
}

