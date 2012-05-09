<?php

namespace Supra\Upgrade\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Supra\Upgrade\Exception;

/**
 * General Upgrade Command
 */
class UpgradeCommand extends Command
{

	/**
	 * @var InputInterface
	 */
	protected $input;

	/**
	 * @var OutputInterface
	 */
	protected $output;

	/**
	 * @return InputInterface
	 */
	public function getInput()
	{
		return $this->input;
	}

	/**
	 * @param InputInterface $input 
	 */
	public function setInput(InputInterface $input)
	{
		$this->input = $input;
	}

	/**
	 * @return OutputInterface
	 */
	public function getOutput()
	{
		return $this->output;
	}

	/**
	 * @param OutputInterface $output 
	 */
	public function setOutput(OutputInterface $output)
	{
		$this->output = $output;
	}

	/**
	 * Configure.
	 */
	protected function configure()
	{
		$this->setName('su:upgrade:all')
				->setDescription('Upgrades Supra installation (database + script).')
				->setHelp('Upgrades Supra installation..')
				->setDefinition(array(
					new InputOption(
							'force', null, InputOption::VALUE_NONE,
							'Actually makes upgrade run.'
					),
					new InputOption(
							'check', null, InputOption::VALUE_NONE,
							'Throws exception if theere are some upgrades pending.'
					),
					new InputOption(
							'list', null, InputOption::VALUE_NONE,
							'Lists all pending upgrades.'
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
		if(! ($input->getOption('force') || $input->getOption('check') || $input->getOption('list')) ){
			throw new Exception\RuntimeException('One of "--force", "--check" or "--list" options must be specified.');
		}
			
		
		$this->setInput($input);
		$this->setOutput($output);

		$output->writeln('Running all upgrades...');

		$this->runCommand('su:upgrade:database');
		$this->runCommand('su:upgrade:script');

		$output->writeln('Done running all upgrades.');
	}

	/**
	 * @param string $commandName
	 * @return integer
	 */
	protected function runCommand($commandName)
	{
		$application = $this->getApplication();

		$input = $this->getInput();

		$array = array($commandName);

		$array['--force'] = $input->getOption('force');
		$array['--check'] = $input->getOption('check');
		$array['--list'] = $input->getOption('list');

		$commandInput = new ArrayInput($array);

		$output = $this->getOutput();

		$application->setAutoExit(false);
		$result = $application->run($commandInput, $output);

		return $result;
	}

}
