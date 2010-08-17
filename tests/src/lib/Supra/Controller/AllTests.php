<?php

namespace Supra\Tests\Controller;

use Supra\Tests\TestSuite;

/**
 * All controller tests
 */
class AllTests
{
	public static function suite()
	{
		$suite = new TestSuite();

		$suite->addTestSuite('Supra\Tests\Controller\Layout\Processor\HtmlTest');
		$suite->addTestSuite('Supra\Tests\Controller\FrontTest');
		$suite->addTestSuite('Supra\Tests\Controller\EmptyControllerTest');
		$suite->addTestSuite('Supra\Tests\Controller\Pages\Fixture\TestFixture');
		
		return $suite;
	}
}