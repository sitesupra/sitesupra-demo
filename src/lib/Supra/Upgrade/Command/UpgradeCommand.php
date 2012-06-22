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
	 * @var boolean
	 */
	private $interactive = false;
	
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
		$options = array(
			'--force' => $input->getOption('force'),
			'--check' => $input->getOption('check'),
			'--list' => $input->getOption('list')
		);
		
		if ( ! ($options['--force'] || $options['--check'] || $options['--list'])) {
			$this->interactive = true;
			$options['--check'] = true;
			$options['--list'] = true;
		}
		
		$this->setInput($input);
		$this->setOutput($output);

		$output->writeln('Running all upgrades...');

		$resultDatabase = $this->runCommand('su:upgrade:database', $options);
		$resultScript = $this->runCommand('su:upgrade:script', $options);
		
		if ( ! $resultDatabase) {
			$force = $this->offerUpgrade('<question>Database is not up to date. Do you want to update now? [y/N]</question> ');
			
			if ($force) {
				$resultDatabase = $this->runCommand('su:upgrade:database', array('--force' => true));
			} else {
				$output->writeln('Skipping database upgrade.');
			}
		}
		
		if ( ! $resultScript) {
			$force = $this->offerUpgrade('<question>There are pending upgrade scripts to be run. Do you want to upgrade now? [y/N]</question> ');
			
			if ($force) {
				$resultDatabase = $this->runCommand('su:upgrade:script', array('--force' => true));
			} else {
				$output->writeln('Skipping upgrade scripts.');
			}
		}

		$output->writeln('Done running all upgrades.');
	}
	
	protected function offerUpgrade($message)
	{
		$dialog = $this->getHelper('dialog');
		$output = $this->getOutput();
		
		$answer = null;
			
		while ( ! in_array($answer, array('Y', 'N', ''), true)) {
			$answer = $dialog->ask($output, $message);
			$answer = strtoupper($answer);
		}

		if ($answer === 'Y') {
			return true;
		}

		return false;
	}

	/**
	 * @param string $commandName
	 * @return integer
	 */
	protected function runCommand($commandName, array $options, $askForcemessage = null)
	{
		$application = $this->getApplication();

		$input = $this->getInput();

		$array = array($commandName);
		
		$array = $array + $options;

		$commandInput = new ArrayInput($array);

		$output = $this->getOutput();

		$application->setAutoExit(false);
		
		try {
			$application->run($commandInput, $output);
		} catch (\Exception $e) {
			
			if ( ! $this->interactive) {
				throw $e;
			}
			
			$this->getApplication()->renderException($e, $output);

			return false;
		}

		return true;
	}

}
