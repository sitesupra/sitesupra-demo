<?php

namespace Supra\Tests;

/**
 * All tests
 */
class AllTests
{
	public static function suite()
	{
		$suite = new TestSuite();

		$suite->addTest(Controller\AllTests::suite());
		$suite->addTest(Ip\AllTests::suite());
		$suite->addTest(Log\AllTests::suite());
		$suite->addTest(NestedSet\AllTests::suite());
		$suite->addTest(Uri\AllTests::suite());

		return $suite;
	}
}