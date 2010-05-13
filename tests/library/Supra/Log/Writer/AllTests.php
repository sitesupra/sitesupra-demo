<?php

namespace Supra\Tests\Log\Writer;

use Supra\Tests\TestSuite;

/**
 * Description of AllTests
 */
class AllTests
{
	public static function suite()
	{
		$suite = new TestSuite();
		
		$suite->addTestSuite('Supra\\Tests\\Log\\Writer\\DailyFileTest');
		
		return $suite;
	}
}