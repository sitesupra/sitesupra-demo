<?php

namespace Supra\Console\Gearman\WorkerFunction;

use Supra\Console\Gearman\WorkerFunctionAbstraction;
use \GearmanJob;

class CommandProxyFunction extends WorkerFunctionAbstraction
{

	/**
	 * @var array
	 */
	protected $allowedCommands;

	/**
	 * @param array $allowedCommands
	 */
	public function setAllowedCommands($allowedCommands)
	{
		$this->allowedCommands = $allowedCommands;
	}

	/**
	 * @param GearmanJob $job
	 */
	public function processJob(GearmanJob $job)
	{
		
	}

}

