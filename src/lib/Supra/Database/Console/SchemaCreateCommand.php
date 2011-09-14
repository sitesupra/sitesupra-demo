<?php

namespace Supra\Database\Console;

use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Doctrine\ORM\Tools\SchemaTool;

/**
 * Schema create command
 *
 */
class SchemaCreateCommand extends SchemaAbstractCommand
{
	
	/**
	 * Configure
	 * 
	 */
	protected function configure()
	{
		$this->setName('su:schema:create')
				->setDescription('Creates ORM schema.')
				->setHelp('Creates ORM schema.');
	}

	/**
	 * Execute command
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output 
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('<comment>ATTENTION</comment>: This operation should not be executed in a production environment.');
		$output->writeln('');
		
		$output->writeln('Creating database schema...');

		// this one sucks compared to update and drop: may raise PDOException
		foreach ($this->entityManagers as $em) {
			$output->writeln($em->_mode);
			$metadatas = $em->getMetadataFactory()->getAllMetadata();
			$schemaTool = new SchemaTool($em);
			$schemaTool->createSchema($metadatas);
		}

		$output->writeln('Database schema created successfully!');
	}
	
}
