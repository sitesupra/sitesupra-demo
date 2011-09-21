<?php

namespace Supra\Tests\Authorization;

use Supra\Tests\TestSuite;

/**
 * All tests
 */
class AllTests
{
	public static function suite()
	{
		$suite = new TestSuite();

		$suite->addTestSuite('Supra\Tests\Authorization\BasicAuthorizationTest');

		return $suite;
	}
}