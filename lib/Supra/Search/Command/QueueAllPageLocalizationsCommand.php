<?php

namespace Supra\Search\Command;

use Symfony\Component\Console;
use Supra\Controller\Pages\Search\PageLocalizationIndexerQueue;
use Doctrine\ORM\EntityManager;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Supra\Controller\Pages\Entity\Abstraction\AbstractPage;
use Supra\Controller\Pages\Entity\Template;

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
		//$schemaNames = PageController::$knownSchemaNames;
		$schemaNames = array(PageController::SCHEMA_PUBLIC);

		foreach ($schemaNames as $schemaName) {

			$output->write('Search: Pages: Reading from schema "' . $schemaName . '" - ');

			$count = $this->addPageLocalizations($schemaName, $output);

			$output->writeln('added ' . intval($count) . ' root page localizations to indexer queue.');
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
	private function addPageLocalizations($schemaName, $output)
	{
		$indexerQueue = new PageLocalizationIndexerQueue($schemaName);

		$em = ObjectRepository::getEntityManager($schemaName);

		$pr = $em->getRepository(AbstractPage::CN());
		$rootNodes = $pr->getRootNodes();

		$counter = 0;
		foreach ($rootNodes as $rootNode) {

			// Skip templates.
			if ( ! $rootNode instanceof Template) {

				foreach ($rootNode->getLocalizations() as $pageLocalization) {
					$indexerQueue->add($pageLocalization);
					$counter++;
				}
			}
		}

//		$repo = $em->getRepository(PageLocalization::CN());
//		$pageLocalizations = $repo->findAll();
//		foreach ($pageLocalizations as $pageLocalization) {
//			//$output->writeln($pageLocalization->getId());
//			$indexerQueue->add($pageLocalization);
//		}
		
		return $counter;
	}

}

