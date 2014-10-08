<?php

namespace Supra\Package\Framework\Command;

use Boris\Boris;
use Supra\Core\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SupraShellCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('supra:shell')
			->setDescription('Starts local RPEL shell with supra loaded. Available variables: $container, $application');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$boris = new Boris('supra> ');
		$boris->setLocal(array(
			'container' => $this->container,
			'application' => $this->getApplication()
		));
		$boris->start();
	}
}
