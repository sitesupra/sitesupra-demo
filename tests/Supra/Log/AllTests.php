<?php

namespace Supra\Tests\Log;

use Supra\Tests\TestSuite;

/**
 * Description of AllTests
 */
class AllTests
{
	public static function suite()
	{
		$suite = new TestSuite();
		
		$suite->addTestSuite('Supra\\Tests\\Log\\LoggerTest');
		$suite->addTest(Writer\AllTests::suite());
		
		return $suite;
	}
}