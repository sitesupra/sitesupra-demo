<?php

namespace Supra\Search\Solarium;

use Solarium_Client;
use Solarium_Exception;
use Solarium_Document_ReadWrite;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\Solarium\Configuration;
use Supra\Search\IndexerServiceAbstract;
use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Supra\Search\IndexerQueueItemStatus;
use Supra\Controller\Pages\Search\PageLocalizationFindRequest;
use Supra\Controller\Pages\PageController;
use Supra\Search\SearchService;

class IndexerService extends IndexerServiceAbstract
{
	/**
	 * Adds $queueItem to Solr.
	 * @param IndexerQueueItem $queueItem 
	 */
	public function processItem(\Supra\Search\Entity\Abstraction\IndexerQueueItem $queueItem)
	{
		$solariumClient = $this->getSolariumClient($this);
		
		if ( ! $solariumClient instanceof \Solarium_Client) {
			
			$message = Configuration::FAILED_TO_GET_CLIENT_MESSAGE;
			\Log::debug($message);
			return 0;
		}

		$documents = array();

		try {
			
			$systemId = $this->getSystemId();
			
			$solariumDocumentWriter = function ($document) use ($solariumClient, $systemId) {
				
				$updateQuery = $solariumClient->createUpdate();

				$document->systemId = $systemId;
				$document->uniqueId = $document->systemId . '-'
						. $document->class . '-'
						. $document->getLocalId();

				\Log::debug('INDEXING UNIQUE ID: ', $document->uniqueId);

				$document->validate();

				$updateQuery->addDocument($document);
				
				$updateQuery->addCommit();

				$result = $solariumClient->update($updateQuery);

				if ($result->getStatus() !== 0) {
					throw new Exception\RuntimeException('Got bad status in update result: ' . $result->getStatus());
				}
			};
			
			$queueItem->writeIndexedDocuments($solariumDocumentWriter);
			
			$queueItem->setStatus(IndexerQueueItemStatus::INDEXED);
		} catch (Exception\BadSchemaException $e) {
			throw $e;
		} catch (Exception\RuntimeException $e) {
			$queueItem->setStatus(IndexerQueueItemStatus::FAILED);
		}
		
		return count($documents);
	}

	public function getSolariumClient()
	{
		if (is_null($this->solariumClient)) {
			if ( ! ObjectRepository::isSolariumConfigured($this)) {
				\Log::debug(Configuration::FAILED_TO_GET_CLIENT_MESSAGE);
				$this->solariumClient = false;
			} else {
				$this->solariumClient = ObjectRepository::getSolariumClient($this);
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

	/**
	 * Remove item from search index
	 * @param type $pageLocalizationId
	 */
	public function remove($pageLocalizationId)
	{
		$findRequest = new PageLocalizationFindRequest();

		$findRequest->setSchemaName(PageController::SCHEMA_PUBLIC);
		$findRequest->setPageLocalizationId($pageLocalizationId);

		$searchService = new SearchService();

		$resultSet = $searchService->processRequest($findRequest);

		$items = $resultSet->getItems();
		
		foreach ($items as $item) {

			if ($item instanceof PageLocalizationSearchResultItem) {

				if ($item->getPageLocalizationId() == $pageLocalizationId) {
					$this->removeFromIndex($item->getUniqueId());
				}
			}
		}
	}
}
