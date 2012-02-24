<?php

namespace Supra\Search\Command;

use Symfony\Component\Console;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\IndexerService;
use Supra\Controller\Pages\Search\PageLocalizationIndexerQueue;
use Supra\Controller\Pages\PageController;
use \Supra\Search\IndexerQueueItemStatus;


/**
 * AuthorizationFixtureCommand
 */
class RunIndexerCommand extends Console\Command\Command
{
	/**
	 */
	protected function configure()
	{
		$this->setName('su:search:run_indexer')
				->setDescription('Indexes all queued documents.')
				->setHelp('Indexes all queued documents.');
	}
	
	/**
	 */
	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$indexerService = new IndexerService();
		$schemaNames = array(PageController::SCHEMA_PUBLIC);

		foreach($schemaNames as $schemaName) {
			
			$pageLocalizationIndexerQueue = new PageLocalizationIndexerQueue($schemaName);
		
			$output->write('Search: Pages: Indexing ' . $pageLocalizationIndexerQueue->getItemCountForStatus(IndexerQueueItemStatus::FRESH) . ' items from queue for schema "' . $schemaName . '" - ');
		
			$documentCount = $indexerService->processQueue($pageLocalizationIndexerQueue);
			
			$output->writeln('done, added ' . intval($documentCount) . ' documents to index.');
		}
		
		$output->writeln('Search: Pages: Indexing done.');
		$output->writeln('');
	}
}