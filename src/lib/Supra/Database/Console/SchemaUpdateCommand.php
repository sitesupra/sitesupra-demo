<?php

namespace Supra\Database\Console;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Events;
use Supra\Database\Upgrade\DatabaseUpgradeRunner;
use Supra\Database\Upgrade\SqlUpgradeFile;

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
					new InputOption(
						'dump-sql', null, InputOption::VALUE_NONE,
						'Causes the generated SQL statements to be output.'
					),
					new InputOption(
						'assert-updated', null, InputOption::VALUE_NONE,
						'Causes exception if schema is not up to date.'
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
        $dumpSql = (true === $input->getOption('dump-sql'));
		$assertUpdated = (true === $input->getOption('assert-updated'));
		$updateRequired = false;
		
		$output->writeln('Updating database schema...');
		
		// Supra upgrade runner
		$supraUpgradeRunner = new DatabaseUpgradeRunner();
		$pendingUpgrades = $supraUpgradeRunner->getPendingUpgrades();
		$output->write('General');
		
		if ( ! empty($pendingUpgrades)) {
			
			$updateRequired = true;
			
			$output->writeln("\t - " . count($pendingUpgrades) . " files");
			
			if ($force) {
				$supraUpgradeRunner->executePendingUpgrades();
			}
			
			if ($dumpSql) {
				$output->writeln('');
				foreach ($pendingUpgrades as $file) {
					/* @var $file SqlUpgradeFile */
					$output->writeln("\t\\. " . $file->getPathname());
				}
				$output->writeln('');
			}
		} else {
			$output->writeln("\t - up to date");
		}
		
		
		// Doctrine schema update
		foreach ($this->entityManagers as $entityManagerName => $em) {

			$output->write($entityManagerName);
			$metadatas = $em->getMetadataFactory()->getAllMetadata();
			$schemaTool = new SchemaTool($em);
			$sqls = $schemaTool->getUpdateSchemaSql($metadatas, true);

			if ( ! empty($sqls)) {
				$updateRequired = true;
				$output->writeln("\t - " . count($sqls) . ' queries');
				
				if ($force) {
					$schemaTool->updateSchema($metadatas, true);
				}
				
				if ($dumpSql) {
					$output->writeln('');
					foreach ($sqls as $sql) {
						$output->writeln("\t" . $sql);
					}
					$output->writeln('');
				}
			} else {
				$output->writeln("\t - up to date");
			}

		}
		

		if ($force) {
			$output->writeln('Database schema updated successfully!');
		}
		
		if ($updateRequired && $assertUpdated) {
			throw new \RuntimeException("Schema is not up to date.");
		}

		if ($updateRequired && ! $force && ! $dumpSql) {
			$output->writeln('');
			$output->writeln('Schema is not up to date.');
			$output->writeln('Please run the operation by passing one of the following options:');
			$output->writeln(sprintf('    <info>%s --force</info> to execute the command', $this->getName()));
			$output->writeln(sprintf('    <info>%s --dump-sql</info> to show the commands', $this->getName()));
		}
	}
	
}
