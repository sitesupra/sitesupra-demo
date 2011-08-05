<?php

namespace Supra\Tests\User;

use Supra\Tests\TestSuite;

/**
 * All tests
 */
class AllTests
{
	public static function suite()
	{
		$suite = new TestSuite();

		$suite->addTestSuite('Supra\Tests\User\UserTest');
		$suite->addTestSuite('Supra\Tests\User\GroupTest');
		
		return $suite;
	}
}