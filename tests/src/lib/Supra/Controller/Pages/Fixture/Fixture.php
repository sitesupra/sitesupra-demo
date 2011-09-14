<?php

namespace Supra\Tests\Controller\Pages\Fixture;

use Supra\Controller\Pages\Entity;
use Supra\Database\Doctrine;
use Doctrine\ORM\Events;

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
			if ($listener instanceof \Supra\Controller\Pages\Listener\PublicVersionedTableIdChange) {
				$listeners = $em->getEventManager()->removeEventListener(Events::loadClassMetadata, $listener);
			}
		}
		
		$helper = new FixtureHelper($em);
		$helper->build();
	}
}
