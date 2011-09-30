<?php

namespace Supra\Tests\Authorization\Fixture;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

class TestFixture extends \PHPUnit_Extensions_OutputTestCase
{
	public function testFixture()
	{
		$fixture = new FixtureHelper('Supra\Tests');
		$fixture->build();
	}
}