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
		$this->root = new Router\UriRouter('/');
		$this->q = new Router\UriRouter('/q');
		$this->qwerty = new Router\UriRouter('/qwerty/');
		$this->quertyAbc = new Router\UriRouter('qwerty/abc');
	}

	function testCompareRouters()
	{
		//self::assertGreaterThan($expected, $actual)
	}
}