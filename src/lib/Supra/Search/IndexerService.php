<?php

namespace Supra\Search;

class IndexerService 
{
	/**
	 * @var self
	 */
	private static $instance;
	
	/**
	 * @var IndexerAbstract
	 */
	protected $indexer;
	
	/**
	 * @TODO: move to object repo
	 * @return \Supra\Search\IndexerService
	 */
	public static function getInstance()
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * @param IndexerAbstract $indexer
	 */
	public function setIndexer(IndexerAbstract $indexer)
	{
		$this->indexer = $indexer;
	}
	
	/**
	 * Takes all FRESH items from $queue and adds them to Solr.
	 * @param IndexerQueue $queue 
	 */
	public function processQueue(IndexerQueue $queue)
	{
//		$indexedQueueItems = array();

		$documentCount = 0;

		while ($queue->getItemCountForStatus(IndexerQueueItemStatus::FRESH) !== 0) {

			$queueItem = $queue->getNextItemForIndexing();

			$documentCount = $documentCount + $this->indexer->processItem($queueItem);
//			$indexedQueueItems[] = $queueItem;

			$queue->store($queueItem);
		}

		//foreach($indexedQueueItems as $indexedQueueItem) {
		//	
		//	$indexedQueueItem->setStatus(IndexerQueueItemStatus::FRESH);
		//	$queue->store($indexedQueueItem);
		//}

		return $documentCount;
	}
	
	public function remove($id)
	{
		return $this->indexer->remove($id);
	}
	
	public function removeAllFromIndex()
	{
		return $this->indexer->removeAllFromIndex();
	}
	
	/**
	 * @return string
	 */
	public function getSystemId()
	{
		return $this->indexer->getSystemId();
	}
}
