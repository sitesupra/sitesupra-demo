<?php

namespace Supra\Tests\Controller\Pages\Fixture;

use Supra\Controller\Pages\Entity,
		Supra\Database\Doctrine;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

class Fixture extends \PHPUnit_Extensions_OutputTestCase
{
	public function testFixture()
	{
		// Find the default entity manager
		$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager('Supra\Cms');
		$helper = new FixtureHelper($em);
		$helper->build();
	}
}
