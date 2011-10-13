<?php

namespace Supra\Tests\Search;

use Supra\Search\IndexerQueue;
use Supra\Tests\Search\Entity\DummyIndexerQueueItem;
use Supra\Tests\Search\DummyItem;

class DummyIndexerQueue extends IndexerQueue
{

	function __construct()
	{
		parent::__construct(DummyIndexerQueueItem::CN());
	}

	/**
	 * @param DummyItem $dummyItem 
	 * @param integer $priority
	 * @return DummyIndexerQueueItem
	 */
	public function add(DummyItem $dummyItem, $priority)
	{
		$indexerQueueItem = new DummyIndexerQueueItem();
		
		$indexerQueueItem->setDummyItem($dummyItem);
		$indexerQueueItem->setPriority($priority);
		
		parent::store($indexerQueueItem);

		return $indexerQueueItem;
	}

	/**
	 * @param DummyItem $dummyItem 
	 * @return DummyIndexerQueueItem
	 */
	public function getIndexerQueueItem($dummyItem)
	{
		$dqb = $this->em->createQueryBuilder();

		$and = $dqb->expr()->andX();
		$and->add($dqb->expr()->eq('iqi.dummyId', $dummyItem->id));
		$and->add($dqb->expr()->eq('iqi.dummyRevision', $dummyItem->revision));

		$dqb->select('iqi')
				->from($this->itemClass, 'iqi')
				->where($and);

		$result = $dqb->getQuery()->execute();

		$indexerQueueItem = $result[0];

		return $indexerQueueItem;
	}

}
