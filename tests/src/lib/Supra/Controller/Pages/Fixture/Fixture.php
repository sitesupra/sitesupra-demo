<?php

namespace Supra\Tests\Controller\Pages\Fixture;

use Doctrine\ORM\Events;
use Supra\Controller\Pages\Listener\PublicVersionedTableIdChange;
use Supra\ObjectRepository\ObjectRepository;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

class Fixture extends \PHPUnit_Extensions_OutputTestCase
{
	public function testFixture()
	{
		// Draft
		$em = ObjectRepository::getEntityManager('Supra\Cms');
		$helper = new FixtureHelper($em);
		$helper->build();
	}
}
