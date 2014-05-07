<?php

namespace Supra\Tests\Search\Mysql;

use Supra\Tests\Search\SearchTestAbstraction;
use Supra\Tests\Search\DummyItem;
use Supra\Tests\Search\Entity\DummyIndexerQueueItem;
use Supra\Search\Mysql\MysqlIndexer;
use Supra\Search\IndexerService;
use Supra\ObjectRepository\ObjectRepository;

class SimpleIndexerServiceTest extends SearchTestAbstraction
{
	/**
	 * @var IndexerService
	 */
	var $indexerService;

	public function setUp()
	{
		$this->indexerService = ObjectRepository::getIndexerService($this);

		$indexer = $this->indexerService->getIndexer();

		if ( ! $indexer instanceof MysqlIndexer) {
			$this->fail('Expecting MysqlIndexer instance, check search.php configuration');
		}

		$indexer->removeAllFromIndex();
	}

	function makeIndexerQueueItem($id, $revision, $text)
	{
		$dummy = new DummyItem($id, $revision, $text);
		
		$dummyIndexerQueueItem = new DummyIndexerQueueItem($dummy);

		return $dummyIndexerQueueItem;
	}

	function testIndexerService()
	{
		$countBefore = $this->indexerService->getDocumentCount();

		self::assertEquals(0, $countBefore);

		$this->indexerService->processItem($this->makeIndexerQueueItem(123, 1, 'ZZZ zz zz desa 123'));
		$this->indexerService->processItem($this->makeIndexerQueueItem(123, 2, 'ZZZ zz zz zupa 123'));
		$this->indexerService->processItem($this->makeIndexerQueueItem(123, 3, 'ZZZ трололо zz desa 888'));

		$this->indexerService->processItem($this->makeIndexerQueueItem(124, 1, 'ZZZ zz zz desa 123'));
		$this->indexerService->processItem($this->makeIndexerQueueItem(125, 1, 'ZZZ zz zz zupa 123'));

		$countAfter = $this->indexerService->getDocumentCount();

		self::assertEquals(5, $countAfter);
	}
}
