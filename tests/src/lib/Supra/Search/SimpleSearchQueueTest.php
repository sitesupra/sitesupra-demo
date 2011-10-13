<?php
 
namespace Supra\Tests\Search;

use Doctrine\ORM\EntityManager;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Tests\Search\Entity\DummyIndexerQueueItem;
use Supra\Tests\Search\DummyItem;
 
class SimpleSearchQueueTest extends \PHPUnit_Framework_TestCase 
{
	/**
	 * @var EntityManager
	 */
	private $em; 
	
	/**
	 * @var DummyIndexerQueue
	 */
	private $iq;
	
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
	
	function testAddToQueue() 
	{
		$dummyItem1 = new DummyItem(111, 50);
		$queueItem1 = $this->iq->add($dummyItem1, 50);
		
		$dummyItem2 = new DummyItem(111, 56);
		$queueItem2 = $this->iq->add($dummyItem2, 80);
		
		$dummyItem3 = new DummyItem(222, 99);
		$queueItem3 = $this->iq->add($dummyItem3, 55);
		
		$this->iq->getStatus();
		
		$queueItem1r = $this->iq->getIndexerQueueItem($dummyItem1);
	}
}
