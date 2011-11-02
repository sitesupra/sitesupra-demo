<?php

namespace Supra\Search\Command;

use Symfony\Component\Console;
use Supra\ObjectRepository\ObjectRepository;
use \Supra\Controller\Pages\PageLocalizationIndexerQueue;

/**
 * AuthorizationFixtureCommand
 */
class WipeQueuesCommand extends Console\Command\Command
{
	/**
	 */
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
		$indexerQueue = new PageLocalizationIndexerQueue();
		
		$indexerQueue->removeAll();
		
		$output->writeln('Removed all items from page localization indexer queues.');
	}
}