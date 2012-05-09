<?php

namespace Supra\Upgrade\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Supra\Upgrade\Script\ScriptUpgradeRunner;
use Supra\Upgrade\Script\ScriptUpgradeFile;
use Supra\Upgrade\Exception;

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
							'force', null, InputOption::VALUE_NONE,
							'Must be specified for upgrades to execute.'
					),
					new InputOption(
							'list', null, InputOption::VALUE_NONE,
							'Lists files of upgrades to be executed.'
					),
					new InputOption(
							'check', null, InputOption::VALUE_NONE,
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
		$output->writeln('Running upgrade scripts...');

		$output->writeln('<comment>ATTENTION</comment>: This operation should not be executed in a production environment.');

		$force = (true === $input->getOption('force'));
		$check = (true === $input->getOption('check'));

		$scriptUpgradeRunner = new ScriptUpgradeRunner();
		$scriptUpgradeRunner->setOutput($output);
		$scriptUpgradeRunner->setApplication($this->getApplication());

		$pendingUpgrades = $scriptUpgradeRunner->getPendingUpgrades();

		if (empty($pendingUpgrades)) {
			
			$output->writeln('No pending update files.');
		} else {

			$output->writeln('Have '. count($pendingUpgrades) . ' pending upgrade file(s):');

			$output->writeln('');
			
			foreach ($pendingUpgrades as $file) {
				/* @var $file SqlUpgradeFile */
				$output->writeln("\t\\. " . $file->getPathname());
			}
			
			$output->writeln('');

			if ($check) {
				throw new Exception\RuntimeException('Have pending upgrade script(s) files.');
			}

			if ($force) {
				$scriptUpgradeRunner->executePendingUpgrades();
			}
		}

		$output->writeln('Done running upgrade scripts.');
	}

}
