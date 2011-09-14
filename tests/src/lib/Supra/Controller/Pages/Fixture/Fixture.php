<?php

namespace Supra\Tests\Controller\Pages\Fixture;

use Doctrine\ORM\Events;
use Supra\Controller\Pages\Listener\PublicVersionedTableIdChange;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

class Fixture extends \PHPUnit_Extensions_OutputTestCase
{
	public function testFixture()
	{
		// Draft
		$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager('Supra\Cms');
		$helper = new FixtureHelper($em);
		$helper->build();
		
		// Public
		$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager('');
		
		$listeners = $em->getEventManager()->getListeners(Events::loadClassMetadata);
		
		foreach ($listeners as $listener) {
			if ($listener instanceof PublicVersionedTableIdChange) {
				$listeners = $em->getEventManager()->removeEventListener(Events::loadClassMetadata, $listener);
			}
		}
		
		$helper = new FixtureHelper($em);
		$helper->build();
	}
}
