<?php

namespace Supra\Search;

use Solarium_Client;
use Solarium_Exception;
use Solarium_Document_ReadWrite;
use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\Solarium\Configuration;

class IndexerService
{

	/**
	 * @var \Solarium_Client;
	 */
	private $solariumClient;

	/**
	 * System ID to be used for this project.
	 * @var string
	 */
	private $systemId;

	/**
	 * @return string
	 */
	public function getSystemId()
	{
		if (is_null($this->systemId)) {
			$info = ObjectRepository::getSystemInfo($this);
			$this->systemId = $info->name;
		}

		return $this->systemId;
	}

	/**
	 * Adds $queueItem to Solr.
	 * @param IndexerQueueItem $queueItem 
	 */
	public function processItem(IndexerQueueItem $queueItem)
	{
		$solariumClient = $this->getSolariumClient($this);
		
		if ( ! $solariumClient instanceof \Solarium_Client) {
			$message = Configuration::FAILED_TO_GET_CLIENT_MESSAGE;
			\Log::debug($message);
			return 0;
		}

		$documents = array();

		try {

			$documents = $queueItem->getIndexedDocuments();

			$updateQuery = $solariumClient->createUpdate();

			foreach ($documents as $document) {
				/* @var $document IndexedDocument */

				$document->systemId = $this->getSystemId();
				$document->uniqueId = $document->systemId . '-'
						. $document->class . '-'
						. $document->getLocalId();

				\Log::debug('INDEXING UNIQUE ID: ', $document->uniqueId);

				$document->validate();

				$updateQuery->addDocument($document);
			}

			$updateQuery->addCommit();

			$result = $solariumClient->update($updateQuery);

			if ($result->getStatus() !== 0) {
				throw new Exception\RuntimeException('Got bad status in update result: ' . $result->getStatus());
			}

			$queueItem->setStatus(IndexerQueueItemStatus::INDEXED);
		} catch (Exception\BadSchemaException $e) {
			throw $e;
		} catch (Exception\RuntimeException $e) {
			$queueItem->setStatus(IndexerQueueItemStatus::FAILED);
		}

		return count($documents);
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

			$documentCount = $documentCount + $this->processItem($queueItem);
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

	public function getSolariumClient()
	{
		if (is_null($this->solariumClient)) {
			try {
				$this->solariumClient = ObjectRepository::getSolariumClient($this);
			} catch (\Exception $e) {
				$message = Configuration::FAILED_TO_GET_CLIENT_MESSAGE;
				\Log::debug($message . PHP_EOL . $e->__toString());
				return;
			}
		}

		return $this->solariumClient;
	}

	/**
	 * Returns count of documents indexed for this system
	 * @return integer
	 */
	public function getDocumentCount()
	{
		$solariumClient = $this->getSolariumClient($this);
		
		if ( ! $solariumClient instanceof \Solarium_Client) {
			$message = Configuration::FAILED_TO_GET_CLIENT_MESSAGE;
			\Log::debug($message);
			return 0;
		}
		
		$query = $solariumClient->createSelect();
		$query->setQuery('systemId:' . $this->getSystemId());
		$query->setRows(0);

		$result = $solariumClient->select($query);

		return $result->getNumFound();
	}

	/**
	 * @param string $uniqueId 
	 */
	public function removeFromIndex($uniqueId)
	{
		$solariumClient = $this->getSolariumClient($this);
		
		if ( ! $solariumClient instanceof \Solarium_Client) {
			$message = Configuration::FAILED_TO_GET_CLIENT_MESSAGE;
			\Log::debug($message);
			return;
		}
		
		$query = $solariumClient->createUpdate();

		$query->addDeleteById($uniqueId);

		$query->addCommit();

		$solariumClient->execute($query);
	}

}
