<?php

namespace Supra\Upgrade\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Supra\Upgrade\Database\DatabaseUpgradeRunner;
use Supra\Upgrade\Database\SqlUpgradeFile;
use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Supra\Console\Output\ArrayOutput;

/**
 * Database upgrade command
 */
class DatabaseUpgradeCommand extends Command
{

	protected function configure()
	{
		$this->setName('su:upgrade:database')
				->setDescription('Runs database upgrades.')
				->setHelp('Upgrades database.')
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
							'Causes exception if database is not up to date.'
					),
				));
	}

	public function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('<comment>ATTENTION</comment>: This operation should not be executed in a production environment.');
		$output->writeln('');

		$force = (true === $input->getOption('force'));
		$dumpSql = (true === $input->getOption('dump-sql'));
		$assertUpdated = (true === $input->getOption('assert-updated'));
		$updateRequired = false;


		$output->writeln('Upgrading database...');

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


		$args = array('su:schema:update');

		if ($force) {
			$args['--force'] = true;
		}
		if ($dumpSql) {
			$args['--dump-sql'] = true;
		}

		if ($assertUpdated) {
			$args['--assert-updated'] = true;
		}

		$input = new ArrayInput($args);

		$this->getApplication()->run($input, $output);
	}

}