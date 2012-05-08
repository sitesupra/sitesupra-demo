<?php

namespace Supra\Upgrade\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * General Upgrade Command
 */
class UpgradeCommand extends Command
{

	/**
	 * Configure
	 * 
	 */
	protected function configure()
	{
		$this->setName('su:upgrade:all')
				->setDescription('Upgrades Supra installation (database + script).')
				->setHelp('Upgrades Supra installation..')
				->setDefinition(array(
					new InputOption(
							'force', null, InputOption::VALUE_NONE,
							'Causes the generated SQL statements to be physically executed against your database.'
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
		
	}

}
