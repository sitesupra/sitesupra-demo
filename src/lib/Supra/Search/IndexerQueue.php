<?php

namespace Supra\Search;

use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Doctrine\ORM\EntityManager;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\EntityRepository;


abstract class IndexerQueue
{

	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @var string
	 */
	protected $itemClass;

	/**
	 * @var EntityRepository
	 */
	protected $repository;

	function __construct($itemClass)
	{
		$this->itemClass = $itemClass;
		$this->em = ObjectRepository::getEntityManager($this);
		$this->repository = $this->em->getRepository($this->itemClass);
	}

	public function store(IndexerQueueItem $item)
	{
		$this->em->persist($item);
		$this->em->flush();
	}

	public function getStatus()
	{
		$dqb = $this->em->createQueryBuilder();

		$dqb->select('iq.status as itemStatus', 'count(iq.id) AS itemCount')
				->from($this->itemClass, 'iq')
				->groupBy('iq.status');

		$queryResult = $dqb->getQuery()->getScalarResult();

		// Fill result array with 0 for all known statuses.
		$result = array_fill_keys(IndexerQueueItemStatus::getKnownStatuses(), 0);

		foreach ($queryResult as $status) {
			$result[$status['itemStatus']] = intval($status['itemCount']);
		}

		return $result;
	}

	public function getItemCountForStatus($status)
	{
		$dql = 'SELECT count(iq.id) AS itemCount FROM ' . $this->itemClass . ' iq WHERE iq.status = ' . $status;
		$queryResult = $this->em->createQuery($dql)->getOneOrNullResult();

		$itemCount = intval($queryResult['itemCount']);

		return $itemCount;
	}

	/**
	 * Retrieves first item sorting by priority (ascending) and create time (descending).
	 * @param integer $status
	 * @param boolean $lock
	 * @return IndexerQueueItem
	 */
	private function getNextItemForStatus($status, $lock = false)
	{
		$status = IndexerQueueItemStatus::validate($status);

		$dqb = $this->em->createQueryBuilder();

		$dqb->select('iq')
				->from($this->itemClass, 'iq')
				->where($dqb->expr()->eq('iq.status', $status))
				->orderBy('iq.priority', 'DESC')
				->orderBy('iq.creationTime', 'ASC')
				->setMaxResults(1);

		//if ($lock) {
		//	$dqb->forUpdate(true);
		//}

		$result = $dqb->getQuery()->getOneOrNullResult();

		return $result;
	}

	/**
	 * Returns next queue item to be indexed.
	 * @return IndexerQueueItem
	 */
	public function getNextItemForIndexing()
	{
		$queueItem = $this->getNextItemForStatus(IndexerQueueItemStatus::FRESH, true);

		if ( ! empty($queueItem)) {

			$queueItem->setStatus(IndexerQueueItemStatus::PROCESSING);
			$this->store($queueItem);
		}

		return $queueItem;
	}
	
	/**
	 * Adds $object to queue. If this $object already is 
	 * in queue and is not yet indexed, existing item will be removed, 
	 * e.i - no two fresh queue items for same $object.
	 * @param type $object
	 * @param type $priority 
	 */
	public function add($object, $priority = IndexerQueueItem::DEFAULT_PRIORITY)
	{
		$queueItem = $this->getOneByObjectAndStatus($object, IndexerQueueItemStatus::FRESH);

		if ( ! empty($queueItem)) {

			$this->em->remove($queueItem);
			$this->em->flush();
		}

		/* @var $newQueueItem IndexerQueueItem */
		$newQueueItem = new $this->itemClass($object);
		$newQueueItem->setPriority($priority);
		
		$this->store($newQueueItem);
		
		return $newQueueItem;
	}
	
	public function removeAll() 
	{
		$query = $this->em->createQuery('DELETE FROM ' . IndexerQueueItem::CN());
		$query->execute();
	}

	abstract function getOneByObjectAndStatus($object, $status);
}
