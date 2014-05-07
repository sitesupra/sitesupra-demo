<?php
 
namespace Supra\Tests\Search;

use Supra\Tests\Search\DummyItem;
use Supra\Search\IndexerQueueItemStatus;
 
class SimpleIndexerQueueTest extends SearchTestAbstraction
{
	function testAddToQueue() 
	{
		$dummyItem1 = new DummyItem(111, 50, '');

		$q1 = $this->iq->add($dummyItem1, 50);
		
		$q1->setStatus(IndexerQueueItemStatus::INDEXED);
		$this->iq->store($q1);

		$dummyItem2 = new DummyItem(111, 56, '');
		$queueItem2 = $this->iq->add($dummyItem2, 80);
		
		$dummyItem3 = new DummyItem(222, 99, '');
		$this->iq->add($dummyItem3, 55);
		
		$testQueueItem = $this->iq->getIndexerQueueItem($dummyItem2);
		
		$queueStatus = $this->iq->getStatus();
		
		self::assertEquals($testQueueItem->getId(), $queueItem2->getId());

		self::assertEquals($queueStatus[IndexerQueueItemStatus::FRESH], 2);
		self::assertEquals($queueStatus[IndexerQueueItemStatus::INDEXED], 1);
	}
}
