<?php

namespace Supra\Search\Command;

use Symfony\Component\Console;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\IndexerService;
use Supra\Search\Solarium\Configuration;

/**
 * AuthorizationFixtureCommand
 */
class WipeCommand extends Console\Command\Command
{
	/**
	 */
	protected function configure()
	{
		$this->setName('su:search:wipe')
				->setDescription('Removes all indexed documents.')
				->setHelp('Removes all indexed documents.');
	}
	
	/**
	 */
	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$indexerService = ObjectRepository::getIndexerService($this);
				
		$indexerService->removeAllFromIndex();
		
		$output->writeln('Search: Indexes for systemId "' . $indexerService->getSystemId() . '" wiped.');
		$output->writeln('');
	}
}