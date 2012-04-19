<?php

namespace Supra\Search\Command;

use Symfony\Component\Console;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Search\PageLocalizationIndexerQueue;
use Supra\Controller\Pages\PageController;

/**
 * AuthorizationFixtureCommand
 */
class WipeQueuesCommand extends Console\Command\Command
{

	protected function configure()
	{
		$this->setName('su:search:wipe_queues')
				->setDescription('Deletes everything from indexer queues.')
				->setHelp('Deletes everything from indexer queues.');
	}

	/**
	 * @param Console\Input\InputInterface $input
	 * @param Console\Output\OutputInterface $output 
	 */
	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$schemaNames = array(PageController::SCHEMA_PUBLIC);
		
		foreach ($schemaNames as $schemaName) {
			
			$output->write('Search: Pages: Indexer queue of schema "' . $schemaName . '" - ');
				
			$indexerQueue = new PageLocalizationIndexerQueue($schemaName);
			$indexerQueue->removeAll();
			
			$output->writeln('wiped.');
		}

		$output->writeln('Search: Pages: Removed all items from page localization indexer queues.');
		$output->writeln('');
	}

}