<?php

namespace Supra\Console\Gearman;

use \GearmanJob;

abstract class WorkerFunctionAbstraction
{

	abstract function processJob(GearmanJob $job);

	/**
	 * @return string
	 */
	static function CN()
	{
		return get_called_class();
	}

}

