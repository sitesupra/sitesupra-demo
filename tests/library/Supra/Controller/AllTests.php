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

		$suite->addTestSuite('Supra\\Tests\\Controller\\FrontTest');
		
		return $suite;
	}
}