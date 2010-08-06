<?php

namespace Supra\Tests\Ip;

use Supra\Tests\TestSuite;

/**
 * All IP tests
 */
class AllTests
{
	public static function suite()
	{
		$suite = new TestSuite();

		$suite->addTestSuite('Supra\\Tests\\Ip\\RangeTest');
		
		return $suite;
	}
}