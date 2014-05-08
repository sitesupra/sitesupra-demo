<?php

namespace Supra\Tests\Search;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Tests\Search\Entity\DummyIndexerQueueItem;
use Supra\Tests\Search\DummySearchRequest;
use Supra\Tests\Search\DummyItem;
use Supra\Search\IndexerService;
use Supra\Search\SearchService;
use Supra\Search\Solarium\SolariumIndexer;
use Supra\Search\Solarium\SolariumSearcher;

class SimpleSearchServiceTest extends SearchTestAbstraction
{
	/**
	 * @var IndexerService
	 */
	var $indexerService;

	/**
	 * @var SearchService
	 */
	var $searchService;

	public function setUp()
	{
		$this->indexerService = ObjectRepository::getIndexerService($this);
		$this->searchService = ObjectRepository::getSearchService($this);

		$indexer = $this->indexerService->getIndexer();
		$searcher = $this->searchService->getSearcher();

		if ( ! $indexer instanceof SolariumIndexer) {
			$this->fail('Expecting SolariumIndexer instance, check search.php configuration');
		}

		if ( ! $searcher instanceof SolariumSearcher) {
			$this->fail('Expecting SolariumSearcher instance, check search.php configuration');
		}

		$client = $indexer->getSolariumClient();

		try {
			$client->ping($client->createPing());
		}
		catch (\Solarium_Client_HttpException $e) {
			self::markTestSkipped("Solr server ping failed with exception {$e->__toString()}");
		}

		$update = $client->createUpdate();

		$query = 'systemId:' . $this->indexerService->getSystemId();
		$update->addDeleteQuery($query);
		$update->addCommit();

		try {
			$client->update($update);
		}
		catch (\Solarium_Client_HttpException $e) {
			self::markTestSkipped("Solr server update failed with exception {$e->__toString()}");
		}

		$this->indexerService->processItem($this->makeIndexerQueueItem(123, 1, 'ZZZ zz zz desa 123'));
		$this->indexerService->processItem($this->makeIndexerQueueItem(123, 2, 'ZZZ zz zz zupa 123'));
		$this->indexerService->processItem($this->makeIndexerQueueItem(123, 3, 'ZZZ трололо zz desa 888'));
		$this->indexerService->processItem($this->makeIndexerQueueItem(124, 1, 'ZZZ zz zz desa 123'));
		$this->indexerService->processItem($this->makeIndexerQueueItem(125, 1, 'ZZZ zz siers zupa 123'));
	}

	function makeIndexerQueueItem($id, $revision, $text)
	{
		$dummy = new DummyItem($id, $revision, $text);
		$dummyIndexerQueueItem = new DummyIndexerQueueItem($dummy);

		return $dummyIndexerQueueItem;
	}

	function testSearchService()
	{
		$r = new DummySearchRequest();
		$r->setText('zz siers');
		$r->setHilightingOptions('text', '<b>', '</b>');
		
		$results = $this->searchService->processRequest($r);

		//TODO: fix highlighting test, $results is array now not an object
//		$highlighting = $results->getHighlighting();
//
//		foreach ($results as $document) {
//			
//			$highlightedDoc = $highlighting->getResult($document->uniqueId);
//
//			if ($highlightedDoc) {
//				
//				foreach ($highlightedDoc as $highlight) {
//					\Log::debug('ZZZ: ', join('( ... )', $highlight));
//				}
//			}
//		}

		//\Log::debug('RESULTS: ', $results->getData());
	}

}
