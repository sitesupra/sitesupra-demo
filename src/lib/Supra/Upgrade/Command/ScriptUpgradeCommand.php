<?php

namespace Supra\Upgrade\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Supra\Upgrade\Script\ScriptUpgradeRunner;
use Supra\Upgrade\Script\ScriptUpgradeFile;

/**
 * Runs upgrade scripts.
 */
class ScriptUpgradeCommand extends Command
{

	/**
	 * Configure
	 * 
	 */
	protected function configure()
	{
		$this->setName('su:upgrade:script')
				->setDescription('Runs installation upgrade scripts.')
				->setHelp('Runs upgrade scripts.')
				->setDefinition(array(
					new InputOption(
							'assert-updated', null, InputOption::VALUE_NONE,
							'Causes exception if upgrade scripts have not been executed.'
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

		//$assertUpdated = (true === $input->getOption('assert-updated'));
		$updateRequired = false;

		$output->writeln('Running upgrade scripts...');

		$scriptUpgradeRunner = new ScriptUpgradeRunner();
		$scriptUpgradeRunner->setOutput($output);
		$scriptUpgradeRunner->setApplication($this->getApplication());
		
		$pendingUpgrades = $scriptUpgradeRunner->getPendingUpgrades();

		if ( ! empty($pendingUpgrades)) {

			$updateRequired = true;

			$output->writeln("\t - " . count($pendingUpgrades) . " files");

			$scriptUpgradeRunner->executePendingUpgrades();

			$output->writeln('');
			foreach ($pendingUpgrades as $file) {
				/* @var $file SqlUpgradeFile */
				$output->writeln("\t\\. " . $file->getPathname());
			}
		} else {
			$output->writeln("\t - installation is up to date.");
		}
	}

}
