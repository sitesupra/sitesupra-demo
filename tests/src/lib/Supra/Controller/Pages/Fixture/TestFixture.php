<?php

namespace Supra\Tests\Controller\Pages\Fixture;

use Supra\Controller\Pages\Entity,
		Supra\Database\Doctrine;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

class TestFixture extends \PHPUnit_Extensions_OutputTestCase
{
	public function testFixtureCommand()
	{
		throw new \LogicException("This script is not in working condition right now. "
				. "It tries to write draft in test_* tables and publish in su_* tables. "
				. "Also it removes pages from audit and public schemes.");
		
		// Find the test entity manager
		$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($this);
		$helper = new FixtureHelper($em);
		$helper->build();
	}
}