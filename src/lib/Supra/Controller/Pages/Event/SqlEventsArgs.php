<?php

namespace Supra\Controller\Pages\Event;

use Supra\Event\EventArgs;

/**
 * Arguments class for SqlEvents
 */
class SqlEventsArgs extends EventArgs
{
	
	public $sql;
	public $params = null; 
	public $types = null;
	public $startTime;
	public $stopTime;
	
}
