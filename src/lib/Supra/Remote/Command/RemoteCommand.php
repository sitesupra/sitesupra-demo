<?php

namespace Supra\Remote\Command;

use Symfony\Component\Console\Command\Command;
use Supra\Log\Log;
use Supra\Console\Output\ArrayOutput;
use Supra\ObjectRepository\ObjectRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Supra\Remote\Client\RemoteCommandService;

class RemoteCommand extends Command
{

	protected function configure()
	{
		$this->setName('su:remote')
				->setDescription('Invokes commands on remote Supra instances.')
				->setHelp('Invokes commands on remote Supra instances.')
				->setDefinition(new InputDefinition(array(
							new InputOption('remote', null, InputOption::VALUE_REQUIRED, 'Remote API endpoint name.'),
							new InputOption('command', null, InputOption::VALUE_REQUIRED, 'Command to execute, including arguments and parameters.'),
						)));
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		//$remoteCommandService = ObjectRepository::getRemoteCommandService($this);
		$remoteCommandService = new RemoteCommandService();

		$remoteCommandInput = new StringInput($input->getOption('command'));
		$remoteCommandOutput = new ConsoleOutput();

		$successIfZero = $remoteCommandService->execute(
				$input->getOption('remote'), $remoteCommandInput, $remoteCommandOutput
		);
		
		if($successIfZero != 0) {
			$output->writeln('FAIL :/');
		}
		
	}

}
