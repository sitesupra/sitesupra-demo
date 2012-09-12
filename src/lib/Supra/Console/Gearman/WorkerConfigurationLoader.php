<?php

namespace Supra\Console\Gearman;

use Supra\Configuration\Loader\ComponentConfigurationLoader;
use \GearmanWorker;

class WorkerConfigurationLoader extends ComponentConfigurationLoader
{

	/**
	 * @var GearmanWorker
	 */
	protected $worker;

	/**
	 * @return GearmanWorker
	 */
	public function getWorker()
	{
		return $this->worker;
	}

	/**
	 * @param GearmanWorker $worker
	 */
	public function setWorker(GearmanWorker $worker)
	{
		$this->worker = $worker;
	}

}
