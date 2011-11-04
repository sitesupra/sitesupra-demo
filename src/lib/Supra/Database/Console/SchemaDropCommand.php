<?php

namespace Supra\Database\Console;

use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Doctrine\ORM\Tools\SchemaTool;

/**
 * Schema drop command
 *
 */
class SchemaDropCommand extends SchemaAbstractCommand
{
	
	/**
	 * Configure
	 * 
	 */
	protected function configure()
	{
		$this->setName('su:schema:drop')
				->setDescription('Drops ORM schema.')
				->setHelp('Drops ORM schema.')
				->setDefinition(array(
					new InputOption(
						'force', null, InputOption::VALUE_NONE,
						'Causes ORM schema to be physically dropped.'
					),
				));
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
		
        $force = (true === $input->getOption('force'));
		
		if ($force) {
			$output->writeln('Dropping database schema...');

			foreach ($this->entityManagers as $entityManagerName => $em) {
				$output->write($entityManagerName);
				$metadatas = $em->getMetadataFactory()->getAllMetadata();
				$schemaTool = new SchemaTool($em);
				$sqls = $schemaTool->getDropSchemaSQL($metadatas);
				if (! empty($sqls)) {
					$schemaTool->dropSchema($metadatas);
					$output->writeln("\t - " . count($sqls) . ' queries executed');
				} else {
					$output->writeln("\t - nothing to drop");
				}
			}

			$output->writeln('Database schema dropped successfully!');
		} else {
            $output->writeln('Please run the operation by passing one of the following options:');
            $output->writeln(sprintf('    <info>%s --force</info> to execute the command', $this->getName()));
		}
	}
	
}
