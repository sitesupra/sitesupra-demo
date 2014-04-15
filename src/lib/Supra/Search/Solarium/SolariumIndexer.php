<?php

namespace Supra\Search\Solarium;

use Solarium_Client;
use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Supra\Search\IndexerQueueItemStatus;
use Supra\Controller\Pages\Search\PageLocalizationFindRequest;
use Supra\Controller\Pages\PageController;
use Supra\Search\SearchService;

class SolariumIndexer extends \Supra\Search\IndexerAbstract
{
	/**
	 * @var Solarium_Client
	 */
	protected $solariumClient;

	/**
	 * @param Solarium_Client $solariumClient
	 */
	public function __construct(Solarium_Client $solariumClient)
	{
		$this->solariumClient = $solariumClient;
	}
	
	/**
	 * Adds $queueItem to Solr.
	 * @param IndexerQueueItem $queueItem 
	 */
	public function processItem(\Supra\Search\Entity\Abstraction\IndexerQueueItem $queueItem)
	{
		$documents = array();

		try {
			
			$systemId = $this->getSystemId();
			
			$solariumClient = $this->solariumClient;
			
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

	/**
	 * Returns count of documents indexed for this system
	 * @return integer
	 */
	public function getDocumentCount()
	{
		$query = $this->solariumClient->createSelect();
		$query->setQuery('systemId:' . $this->getSystemId());
		$query->setRows(0);

		$result = $this->solariumClient->select($query);

		return $result->getNumFound();
	}

	/**
	 * @param string $uniqueId 
	 */
	public function removeFromIndex($uniqueId)
	{
		$query = $this->solariumClient->createUpdate();

		$query->addDeleteById($uniqueId);

		$query->addCommit();

		$this->solariumClient->execute($query);
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

		$searchService = SearchService::getInstance();

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

	public function removeAllFromIndex()
	{
		$update = $this->solariumClient->createUpdate();
		
		$query = 'systemId:' . $this->getSystemId();
		
		$update->addDeleteQuery($query);
		$update->addCommit();
		
		$this->solariumClient->update($update);
	}
}
