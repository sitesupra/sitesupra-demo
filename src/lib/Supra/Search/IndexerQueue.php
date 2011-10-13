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
		$dql = "SELECT count(iq.id) AS itemCount FROM " . $this->itemClass . " iq";
		$itemCount = $this->em->createQuery($dql)->getScalarResult();

		\Log::debug('GET STATUS: ', $itemCount);

		return array();
	}

	/**
	 * Returns next queue item to be indexed.
	 * @return IndexerQueueItem
	 */
	public function getNextItemForIndexing()
	{
		
	}
	
	abstract function getIndexerQueueItem($object);

}
