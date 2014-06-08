<?php

namespace Supra\Search;

class IndexerService 
{
	/**
	 * @var AbstractIndexer
	 */
	protected $indexer;
	
	public function __construct(AbstractIndexer $indexer)
	{
		if ($indexer !== null) {
			$this->indexer = $indexer;
		}
	}

	/**
	 * @return AbstractIndexer
	 */
	public function getIndexer()
	{
		return $this->indexer;
	}
	
	/**
	 * @param AbstractIndexer $indexer
	 */
	public function setIndexer(AbstractIndexer $indexer)
	{
		$this->indexer = $indexer;
	}
	
	/**
	 * Takes all FRESH items from $queue and adds them to Solr.
	 * @param IndexerQueue $queue 
	 */
	public function processQueue(IndexerQueue $queue)
	{
		$documentCount = 0;

		while ($queue->getItemCountForStatus(IndexerQueueItemStatus::FRESH) !== 0) {

			$queueItem = $queue->getNextItemForIndexing();

			$documentCount = $documentCount + $this->indexer->processItem($queueItem);

			$queue->store($queueItem);
		}

		return $documentCount;
	}

	/**
	 * @return int
	 */
	public function getDocumentCount()
	{
		return $this->indexer->getDocumentCount();
	}

	public function processItem(Entity\Abstraction\IndexerQueueItem $item)
	{
		return $this->indexer->processItem($item);
	}

	/**
	 * @param string $id
	 * @return void
	 */
	public function remove($id)
	{
		return $this->indexer->remove($id);
	}
	
	/**
	 * @return void
	 */
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
