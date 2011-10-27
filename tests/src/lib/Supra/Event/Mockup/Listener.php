<?php

namespace Supra\Tests\Event\Mockup;

class Listener
{
	private $test;
	private $id;
	
	public function __construct(\Supra\Tests\Event\EventManagerTest $test, $id)
	{
		$this->test = $test;
		$this->id = $id;
	}
	
	public function __call($name, $arguments)
	{
		$eventArgs = $arguments[0];
		$this->test->fired($eventArgs, $this->id);
	}
}
