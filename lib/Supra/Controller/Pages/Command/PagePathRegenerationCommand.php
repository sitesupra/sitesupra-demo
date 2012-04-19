<?php

namespace Supra\Controller\Pages\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Supra\Controller\Pages\PageController;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Controller\Pages\Entity\PageLocalizationPath;
use Supra\Controller\Pages\Listener\PagePathGenerator;

/**
 * Generates path for all pages
 */
class PagePathRegenerationCommand extends Command
{
	/**
     * Configures the current command.
     */
    protected function configure()
    {
		$this->setName('su:pages:regenerate_path')
				->setDescription("Regenerates the path for all pages");
    }
	
	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
    {
		$schemas = array(PageController::SCHEMA_DRAFT, PageController::SCHEMA_PUBLIC);
		
		$pathById = array();
		
		foreach ($schemas as $schema) {
			$em = ObjectRepository::getEntityManager($schema);
			$pageLocalizationEntity = PageLocalization::CN();
			$pageLocalizationPathEntity = PageLocalizationPath::CN();
			
			$em->beginTransaction();
			
			$dql = "UPDATE $pageLocalizationEntity l SET l.path = NULL";
			$em->createQuery($dql)
					->getResult();
			
			$dql = "DELETE FROM $pageLocalizationPathEntity";
			$em->createQuery($dql)
					->getResult();
			
			$generator = new PagePathGenerator($em);
			
			$dql = "SELECT l FROM $pageLocalizationEntity l JOIN l.master m ORDER BY m.left ASC";
			$pageLocalizations = $em->createQuery($dql)
					->getResult();
			
			foreach ($pageLocalizations as $pageLocalization) {
				/* @var $pageLocalization PageLocalization */
				
				$id = $pageLocalization->getId();
				
				if (isset($pathById[$id])) {
					$path = clone($pathById[$id]);
					$path->setId($pathById[$id]->getId());
					$pageLocalization->setPathEntity($path);
				}
				
				$generator->generatePath($pageLocalization);
				
				$pathById[$id] = $pageLocalization->getPathEntity();
			}
			
			$em->flush();
			
			$em->commit();
		}
		
		$output->writeln("Done");
	}
}
