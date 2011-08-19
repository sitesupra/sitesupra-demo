<?php

namespace Supra\Tests\Controller\Pages\Fixture;

use Supra\Controller\Pages\Entity,
		Supra\Database\Doctrine;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

class Fixture extends \PHPUnit_Extensions_OutputTestCase
{
	const CONNECTION_NAME = '';

	public function testFixture()
	{
		$helper = new FixtureHelper(static::CONNECTION_NAME);
		$helper->build();
	}
}