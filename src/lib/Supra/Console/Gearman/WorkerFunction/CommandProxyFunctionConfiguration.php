<?php

namespace Supra\Console\Gearman\WorkerFunction;

use Supra\Console\Gearman\WorkerFunctionConfiguration;
use Supra\Console\Gearman\WorkerFunctionAbstraction;

class CommandProxyFunctionConfiguration extends WorkerFunctionConfiguration
{

	/**
	 * @var array
	 */
	public $allowedCommands;

	/**
	 * 
	 */
	public function configure()
	{
		$this->name = 'runCommand';
		$this->class = CommandProxyFunction::CN();

		parent::configure();
	}

	/**
	 * @return WorkerFunctionAbstraction
	 */
	protected function getWorkerFunctionInstance()
	{
		$workerFunction = new CommandProxyFunction();

		$workerFunction->setAllowedCommands($this->allowedCommands);

		return $workerFunction;
	}

}
