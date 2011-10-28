<?php

namespace Supra\Tests\Search;

use Doctrine\ORM\EntityManager;
use Supra\ObjectRepository\ObjectRepository;

abstract class SearchTestAbstraction extends \PHPUnit_Framework_TestCase
{

	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @var DummyIndexerQueue
	 */
	protected $iq;

	function setUp()
	{
		$this->em = ObjectRepository::getEntityManager($this);

		$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);

		$metaDatas = $this->em->getMetadataFactory()->getAllMetadata();
		$classFilter = function(\Doctrine\ORM\Mapping\ClassMetadata $classMetadata) {
					return (
							strpos($classMetadata->namespace, 'Supra\Search\Entity') === 0 ||
							strpos($classMetadata->namespace, 'Supra\Tests\Search\Entity') === 0
							);
				};
		$metaDatas = \array_filter($metaDatas, $classFilter);

		$schemaTool->dropSchema($metaDatas);
		$schemaTool->updateSchema($metaDatas, true);

		$this->iq = new DummyIndexerQueue();
		ObjectRepository::setDefaultIndexerQueue($this->iq);
	}

}
