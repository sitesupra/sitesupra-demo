<?php

namespace Supra\Tests\Search\Mysql;

use Supra\Tests\Search\SearchTestAbstraction;
use Supra\Tests\Search\Entity\DummyIndexerQueueItem;
use Supra\Tests\Search\DummySearchRequest;
use Supra\Tests\Search\DummyItem;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\IndexerService;
use Supra\Search\SearchService;
use Supra\Search\Mysql\MysqlIndexer;
use Supra\Search\Mysql\MysqlSearcher;
use Supra\Locale\Locale;

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

	/**
	 * @var Locale
	 */
	var $locale;

	public function setUp()
	{
		$this->indexerService = ObjectRepository::getIndexerService($this);
		$this->searchService = ObjectRepository::getSearchService($this);

		$indexer = $this->indexerService->getIndexer();
		$searcher = $this->searchService->getSearcher();

		if ( ! $indexer instanceof MysqlIndexer) {
			$this->fail('Expecting MysqlIndexer instance, check search.php configuration');
		}

		if ( ! $searcher instanceof MysqlSearcher) {
			$this->fail('Expecting MysqlSearcher instance, check search.php configuration');
		}

		$this->locale = ObjectRepository::getLocaleManager($this)
				->getCurrent();

		$indexer->removeAllFromIndex();

		$this->indexerService->processItem($this->makeIndexerQueueItem(123, 1, 'ZZZ zz zz desa 123'));
		$this->indexerService->processItem($this->makeIndexerQueueItem(123, 2, 'ZZZ zz zz zupa 123'));
		$this->indexerService->processItem($this->makeIndexerQueueItem(123, 3, 'ZZZ трололо zz desa 888'));
		$this->indexerService->processItem($this->makeIndexerQueueItem(124, 1, 'ZZZ zz zz desa 123'));
		$this->indexerService->processItem($this->makeIndexerQueueItem(125, 1, 'ZZZ zz siers zupa 123'));
	}

	function makeIndexerQueueItem($id, $revision, $text)
	{
		$dummy = new DummyItem($id, $revision, $text);

		$dummy->localeId = $this->locale->getId();

		$dummyIndexerQueueItem = new DummyIndexerQueueItem($dummy);

		return $dummyIndexerQueueItem;
	}

	function testSearchService()
	{
		// @FIXME: MysqlSearcher now accepts only PageLocalizationSearchRequest
		// must accept all the SearchRequestInterface classes
		$request = new \Supra\Controller\Pages\Search\PageLocalizationSearchRequest();
		$request->setText('zz siers');
		$request->setHilightingOptions('text', '<b>', '</b>');
		$request->setResultMaxRows(10);

		$request->setLocale($this->locale);

		$results = $this->searchService->processRequest($request);

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
