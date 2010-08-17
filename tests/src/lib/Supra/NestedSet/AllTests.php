<?php

namespace Supra\Tests\NestedSet;

use Supra\Tests\TestSuite;

/**
 * All tests
 */
class AllTests
{
	public static function suite()
	{
		$suite = new TestSuite();

		$suite->addTestSuite('Supra\Tests\NestedSet\Node\ArrayNodeTest');
		$suite->addTestSuite('Supra\Tests\NestedSet\Node\DoctrineNodeTest');

		return $suite;
	}
}