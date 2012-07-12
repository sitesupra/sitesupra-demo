<?php

namespace Supra\FileStorage\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\FileStorage\Entity\Abstraction\File;
use Supra\FileStorage\Entity\FilePath;
use Supra\Controller\Pages\PageController;

/**
 * Generates path for all pages
 */
class RegenerateFilePathCommand extends Command
{

	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this->setName('su:files:regenerate_path')
				->setDescription("Regenerates the path for all files");
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{

		$pathById = array();

		$em = ObjectRepository::getEntityManager(PageController::SCHEMA_PUBLIC);
		$em->beginTransaction();

		$em->createQuery('UPDATE ' . File::CN() . ' f SET f.path = NULL')->getResult();
		$em->createQuery('DELETE FROM ' . FilePath::CN())->getResult();
		$files = $em->createQuery('SELECT f FROM ' . File::CN() . ' f ORDER BY f.left ASC')->getResult();

		$generator = new \Supra\FileStorage\Listener\FilePathGenerator($em);

		$i = 0;
		foreach ($files as $file) {
			/* @var $file PageLocalization */
			$id = $file->getId();

			if (isset($pathById[$id])) {
				$path = clone($pathById[$id]);
				$path->setId($pathById[$id]->getId());
				$file->setPathEntity($path);
			}

			$generator->regeneratePath($file);

			$pathById[$id] = $file->getPathEntity();

			$i ++;

			if ($i % 10 == 0) {
				$output->writeln("Processed $i files");
			}
		}

		$output->writeln("Done generating. Flushing to database...");

		$em->flush();
		$em->commit();

		$output->writeln("Done. Processed $i files");
	}

}
