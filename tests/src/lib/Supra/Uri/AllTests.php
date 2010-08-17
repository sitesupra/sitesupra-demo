<?php

namespace Supra\Tests\Uri;

use Supra\Tests\TestSuite;

/**
 * All tests
 */
class AllTests
{
	public static function suite()
	{
		$suite = new TestSuite();

		$suite->addTestSuite('Supra\Tests\Uri\PathTest');

		return $suite;
	}
}