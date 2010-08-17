<?php

namespace Supra\Tests\Controller;

use Supra\Tests\TestCase;
use Supra\Controller\EmptyController;
use Supra\Controller\Request;
use Supra\Controller\Response;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

/**
 * Test class for EmptyController
 */
class EmptyControllerTest extends \PHPUnit_Extensions_OutputTestCase
{
	/**
	 * Tests execution
	 */
	public function testExecute()
	{
		$controller = new EmptyController();
		$request = new Request\Cli();
		$response = $controller->createResponse($request);
		$controller->execute($request, $response);
		
		$this->expectOutputString('');
		$response->output('stuff');
		$response->flush();
	}

	/**
	 * Get response test
	 */
	public function testGetResponseObject()
	{
		$controller = new EmptyController();
		$request = new Request\Cli();
		$response = $controller->createResponse($request);
		self::isInstanceOf('Supra\\Controller\\Response\\EmptyResponse')
			->evaluate($response);
	}
}