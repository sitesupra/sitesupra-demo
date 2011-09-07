<?php

namespace Supra\Tests\Controller\Pages\Fixture;

use Supra\Controller\Pages\Entity,
		Supra\Database\Doctrine;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

class TestFixture extends \PHPUnit_Extensions_OutputTestCase
{
	public function testFixture()
	{
		// Find the test entity manager
		$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($this);
		$helper = new FixtureHelper($em);
		$helper->build();
	}
}