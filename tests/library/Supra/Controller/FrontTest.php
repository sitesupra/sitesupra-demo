<?php

namespace Supra\Tests\Controller;

use Supra\Tests\TestCase;
use Supra\Controller\Front;

/**
 * Description of Front
 */
class FrontTest extends TestCase
{
	/**
	 * Test singleton pattern
	 */
	function testGetInstance()
	{
		$instance1 = Front::getInstance();
		$instance2 = Front::getInstance();
		self::isInstanceOf('Supra\\Controller\\Front')->evaluate($instance1);
		self::isInstanceOf('Supra\\Controller\\Front')->evaluate($instance2);
		self::assertEquals($instance1, $instance2);
	}
}