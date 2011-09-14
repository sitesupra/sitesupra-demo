<?php

namespace Supra\Database\Console;

use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Doctrine\ORM\Tools\SchemaTool;

/**
 * Schema update command
 *
 */
class SchemaUpdateCommand extends SchemaAbstractCommand
{
	
	/**
	 * Configure
	 * 
	 */
	protected function configure()
	{
		$this->setName('su:schema:update')
				->setDescription('Updates ORM schema.')
				->setHelp('Updates ORM schema.')
				->setDefinition(array(
					new InputOption(
						'force', null, InputOption::VALUE_NONE,
						'Causes the generated SQL statements to be physically executed against your database.'
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
			$output->writeln('Updating database schema...');

			foreach ($this->entityManagers as $em) {
				$output->write($em->_mode);
				$metadatas = $em->getMetadataFactory()->getAllMetadata();
				$schemaTool = new SchemaTool($em);
				$sqls = $schemaTool->getUpdateSchemaSql($metadatas, true);
				if (! empty($sqls)) {
					$schemaTool->updateSchema($metadatas, true);
					$output->writeln("\t - " . count($sqls) . ' queries');
				} else {
					$output->writeln("\t - nothing to update");
				}
			}

			$output->writeln('Database schema updated successfully!');
		} else {
            $output->writeln('Please run the operation by passing one of the following options:');
            $output->writeln(sprintf('    <info>%s --force</info> to execute the command', $this->getName()));
		}
	}
	
}
