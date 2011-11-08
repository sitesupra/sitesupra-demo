<?php

namespace Supra\Search\Command;

use Symfony\Component\Console;
use Supra\Controller\Pages\Search\PageLocalizationIndexerQueue;
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
		foreach (PageController::$knownSchemaNames as $schemaName) {

			$output->write('Search: Pages: Reading from schema "' . $schemaName . '" - ');
			
			$count = $this->addPageLocalizations($schemaName);

			$output->writeln('added ' . intval($count) . ' page localizations to indexer queue.');
		}

		$output->writeln('Search: Pages: Done adding pages to indexer queue.');
		$output->writeln('');
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

		$indexerQueue = new PageLocalizationIndexerQueue($schemaName);

		foreach ($pageLocalizations as $pageLocalization) {
			$indexerQueue->add($pageLocalization);
		}

		return count($pageLocalizations);
	}

}

