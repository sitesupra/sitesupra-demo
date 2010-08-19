<?php

namespace Supra\Tests\Controller;

use Supra\Tests\TestCase;
use Supra\Controller\Front;

/**
 * Description of Front
 */
class FrontTest extends TestCase
{
	protected $root;
	protected $q;
	protected $qwerty;
	protected $quertyAbc;

	protected function setUp()
	{
		$this->root = new \Supra\Controller\Router\Uri('/');
		$this->q = new \Supra\Controller\Router\Uri('/q');
		$this->qwerty = new \Supra\Controller\Router\Uri('/qwerty/');
		$this->quertyAbc = new \Supra\Controller\Router\Uri('qwerty/abc');

		
	}

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

	function testCompareRouters()
	{
		//self::assertGreaterThan($expected, $actual)
	}
}