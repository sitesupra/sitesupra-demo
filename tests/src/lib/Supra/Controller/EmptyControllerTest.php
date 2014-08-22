<?php

namespace Supra\Tests\Controller;

use Supra\Tests\TestCase;
use Supra\Controller\EmptyController;
use Supra\Request;
use Supra\Response;

//require_once 'PHPUnit/Extensions/OutputTestCase.php';

/**
 * Test class for EmptyController
 */
class EmptyControllerTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Tests execution
	 */
	public function testExecute()
	{
		$controller = new EmptyController();
		$request = new Request\HttpRequest();
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
		$request = new Request\HttpRequest();
		$response = $controller->createResponse($request);
		self::isInstanceOf('Supra\Response\EmptyResponse')
			->evaluate($response);
	}
}