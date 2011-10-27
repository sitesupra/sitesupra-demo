<?php

namespace Supra\Tests\Event\Mockup;

class EventArgs extends \Supra\Event\EventArgs
{
	public $eventType;
	
	public function __construct($eventType)
	{
		$this->eventType = $eventType;
	}
}