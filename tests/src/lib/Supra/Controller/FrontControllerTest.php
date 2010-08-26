<?php

namespace Supra\Tests\Controller;

use Supra\Tests\TestCase,
		Supra\Controller\FrontController,
		Supra\Router;

/**
 * Description of FrontController
 */
class FrontControllerTest extends TestCase
{
	protected $frontController;

	protected $root;
	protected $q;
	protected $qwerty;
	protected $quertyAbc;

	protected function setUp()
	{
		$this->frontController = new FrontController();
		$this->root = new Router\Uri('/');
		$this->q = new Router\Uri('/q');
		$this->qwerty = new Router\Uri('/qwerty/');
		$this->quertyAbc = new Router\Uri('qwerty/abc');
	}

	function testCompareRouters()
	{
		//self::assertGreaterThan($expected, $actual)
	}
}