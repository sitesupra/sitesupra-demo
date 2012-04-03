<?php

namespace Supra\Controller\Event;

use Supra\Event\EventArgs;

class FrontControllerShutdownEventArgs extends EventArgs
{
	public $frontController;
}

