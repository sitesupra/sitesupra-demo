<?php

namespace Supra\Tests\Authorization\Fixture;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\AuthorizationProvider;
use Supra\Authorization\AuthorizationPermission;

use Supra\Cms\CmsApplicationConfiguration;

class Fixture extends \PHPUnit_Framework_TestCase 
{
	function testFixture()
	{
		$fixture = new FixtureHelper('Supra\Cms\CmsController');
		$fixture->build();
	}
}