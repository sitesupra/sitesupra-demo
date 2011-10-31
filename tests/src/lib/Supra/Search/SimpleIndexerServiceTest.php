<?php

namespace Supra\Tests\Search;

use Doctrine\ORM\EntityManager;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Tests\Search\Entity\DummyIndexerQueueItem;
use Supra\Tests\Search\DummyItem;
use Supra\Search\IndexerQueueItemStatus;
use Supra\Search\IndexerService;

class SimpleIndexerServiceTest extends SearchTestAbstraction
{

	/**
	 *
	 * @var IndexerService
	 */
	var $indexerService;

	public function setUp()
	{
		$this->indexerService = new IndexerService();

		$client = $this->indexerService->getSolariumClient();

		try {
			$client->ping($client->createPing());
		} catch (\Solarium_Client_HttpException $e) {
			self::markTestSkipped("Solr server ping failed with exception {$e->__toString()}");
		}
		
		$update = $client->createUpdate();

		$query = 'systemId:' . $this->indexerService->getSystemId();
		$update->addDeleteQuery($query);
		$update->addCommit();
		$client->update($update);
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

		self::assertEquals($countBefore, 0);

		$this->indexerService->processItem($this->makeIndexerQueueItem(123, 1, 'ZZZ zz zz desa 123'));
		$this->indexerService->processItem($this->makeIndexerQueueItem(123, 2, 'ZZZ zz zz zupa 123'));
		$this->indexerService->processItem($this->makeIndexerQueueItem(123, 3, 'ZZZ трололо zz desa 888'));

		$this->indexerService->processItem($this->makeIndexerQueueItem(124, 1, 'ZZZ zz zz desa 123'));
		$this->indexerService->processItem($this->makeIndexerQueueItem(125, 1, 'ZZZ zz zz zupa 123'));

		$countAfter = $this->indexerService->getDocumentCount();

		self::assertEquals($countAfter, 5);
	}

}
