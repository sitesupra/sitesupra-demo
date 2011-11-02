<?php

namespace Supra\Search\Command;

use Symfony\Component\Console;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\IndexerService;
use Supra\Controller\Pages\PageLocalizationIndexerQueue;


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

		$pageLocalizationIndexerQueue = new PageLocalizationIndexerQueue();
		
		$output->writeln('Pages: Have ' . $pageLocalizationIndexerQueue->getItemCountForStatus(\Supra\Search\IndexerQueueItemStatus::FRESH) . ' in queue.');
		
		$indexerService->processQueue($pageLocalizationIndexerQueue);
		
		$output->writeln('Pages: Indexing done.');
	}
}