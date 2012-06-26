<?php

namespace Supra\Upgrade\Script;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

abstract class UpgradeScriptAbstraction
{

	/**
	 * @var Application 
	 */
	protected $application;

	/**
	 *
	 * @var OutputInterface
	 */
	protected $output;

	/**
	 * @return Application 
	 */
	public function getApplication()
	{
		return $this->application;
	}

	/**
	 * @param Application $application 
	 */
	public function setApplication(Application $application)
	{
		$this->application = $application;
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
	 * Determines whether this particular script needs to run now.
	 * @return boolean 
	 */
	public function validate()
	{
		return true;
	}

	/**
	 * Determines whether this particular script needs to be marked as executed.
	 * @return boolean 
	 */
	public function markAsExecuted()
	{
		return true;
	}
	
	/**
	 * Upgrade execution command
	 */
	abstract public function upgrade();

	/**
	 * @param string $commandName
	 * @param array $arguments
	 * @param array $options
	 * @param OutputInterface $output 
	 */
	protected function runCommand($commandName, array $arguments = array(), array $options = array(), OutputInterface $output = null)
	{
		$application = $this->getApplication();

		$inputArray = array($commandName);

		if ( ! empty($arguments)) {

			foreach ($arguments as $key => $value) {
				$inputArray[$key] = $value;
			}
		}

		if ( ! empty($options)) {

			foreach ($options as $key => $value) {
				$inputArray['--' . $key] = $value;
			}
		}

		$input = new ArrayInput($inputArray);

		if (is_null($output)) {
			$output = $this->getOutput();
		}

		$application->setAutoExit(false);
		$result = $application->run($input, $output);

		return $result;
	}

}

