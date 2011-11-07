<?php

namespace Supra\Search\Command;

use Symfony\Component\Console;
use Supra\Controller\Pages\PageLocalizationIndexerQueue;
use Doctrine\ORM\EntityManager;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Search\Entity\Abstraction\IndexerQueueItem;

class QueueAllPageLocalizationsCommand extends Console\Command\Command
{

	/**
	 */
	protected function configure()
	{
		$this->setName('su:search:queue_all_pages')
				->setDescription('Adds all page localizations into indexer queue.')
				->setHelp('Adds all page localizations into indexer queue.');
	}

	/**
	 * @param Console\Input\InputInterface $input
	 * @param Console\Output\OutputInterface $output 
	 */
	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		foreach(PageController::$knownSchemaNames as $schemaName) {
			
			$output->writeln('Pages: schema name:' . $schemaName);
			
			$count = $this->addPageLocalizations($schemaName);
			$output->writeln('Pages: Added ' . intval($count) . ' to indexer queue from schema "' . $schemaName . '".');
		}
	}

	/**
	 * Adds all page localizations found in schema $schemaName to indexer queue.
	 * Returns number of localizations added.
	 * @param string $chemaName
	 * @return number
	 */
	private function addPageLocalizations($schemaName)
	{
		$em = ObjectRepository::getEntityManager($schemaName);

		$repo = $em->getRepository(PageLocalization::CN());

		$pageLocalizations = $repo->findAll();

		$indexerQueue = new PageLocalizationIndexerQueue();

		foreach ($pageLocalizations as $pageLocalization) {
			$indexerQueue->add($pageLocalization, IndexerQueueItem::DEFAULT_PRIORITY, $schemaName);
		}
		
		return count($pageLocalizations);
	}

}

