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
		$indexerService = new IndexerService();

		$client = $indexerService->getSolariumClient();
		if ( ! $client instanceof \Solarium_Client) {
			$message = Configuration::FAILED_TO_GET_CLIENT_MESSAGE;
			$output->writeln($message);
			\Log::debug($message);
			return;
		}

		$update = $client->createUpdate();

		$query = 'systemId:' . $indexerService->getSystemId();
		$update->addDeleteQuery($query);
		$update->addCommit();
		$client->update($update);	
		
		$output->writeln('Search: Indexes for systemId "' . $indexerService->getSystemId() . '" wiped.');
		$output->writeln('');
	}
}