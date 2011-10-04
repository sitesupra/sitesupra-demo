<?php

namespace Supra\Tests\Authorization\Fixture;

use Doctrine\ORM\EntityManager;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

class TestFixture extends \PHPUnit_Extensions_OutputTestCase
{
	public function testFixture()
	{
		/* @var $em EntityManager */
		$em = ObjectRepository::getEntityManager($this->namespace);

		
		// User model drop/create
		$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);

		$metaDatas = $this->em->getMetadataFactory()->getAllMetadata();
		$classFilter = function(\Doctrine\ORM\Mapping\ClassMetadata $classMetadata) {
			return (strpos($classMetadata->namespace, 'Supra\User\Entity') === 0);
		};
		$metaDatas = \array_filter($metaDatas, $classFilter);
		
		$schemaTool->updateSchema($metaDatas, true);
		
		$fixture = new FixtureHelper('Supra\Tests');
		$fixture->build();
	}
}