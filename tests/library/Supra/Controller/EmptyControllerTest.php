<?php

namespace Supra\Tests\Controller;

use Supra\Tests\TestCase;
use Supra\Controller\EmptyController;
use Supra\Controller\Request;
use Supra\Controller\Response;

/**
 * Test class for EmptyController
 */
class EmptyControllerTest extends TestCase
{
	/**
	 * Tests execution
	 */
	public function testExecute()
	{
		$controller = new EmptyController();
		$request = new Request\Http();
		$response = $controller->getResponseObject($request);
		$controller->execute($request, $response);
		ob_start();
		$response->output('stuff');
		$response->flush();
		$content = ob_get_clean();
		self::assertEquals('', $content);
	}

	/**
	 * Get response test
	 */
	public function testGetResponseObject()
	{
		$controller = new EmptyController();
		$request = new Request\Cli();
		$response = $controller->getResponseObject($request);
		self::isInstanceOf('Supra\\Controller\\Response\\EmptyResponse')
			->evaluate($response);
	}
}