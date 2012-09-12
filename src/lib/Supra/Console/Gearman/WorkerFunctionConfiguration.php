<?php

namespace Supra\Console\Gearman;

use Supra\Configuration\ConfigurationInterface;
use Supra\Console\Gearman\WorkerConfigurationLoader;
use Supra\Configuration\Loader\LoaderRequestingConfigurationInterface;
use Supra\Configuration\Loader\ComponentConfigurationLoader;
use Supra\Console\Gearman\WorkerFunctionAbstraction;
use \GearmanWorker;

class WorkerFunctionConfiguration implements ConfigurationInterface, LoaderRequestingConfigurationInterface
{

	/**
	 * @var string
	 */
	public $class;

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var WorkerConfigurationLoader
	 */
	protected $loader;

	/**
	 * @param ComponentConfigurationLoader $loader
	 * @throws Exception\RuntimeException 
	 */
	public function setLoader(ComponentConfigurationLoader $loader)
	{
		if ( ! $loader instanceof WorkerConfigurationLoader) {
			throw new Exception\RuntimeException('WorkerFunctionConfiguration must be used with WorkerConfigurationLoader.');
		}

		$this->loader = $loader;
	}

	/**
	 * @param GearmanWorker $worker 
	 */
	public function setWorker(GearmanWorker $worker)
	{
		$this->loader->setWorker($worker);
	}

	/**
	 * @return GearmanWorker
	 */
	public function getWorker()
	{
		return $this->loader->getWorker();
	}

	/**
	 * @return WorkerFunctionAbstraction
	 */
	protected function getWorkerFunctionInstance()
	{
		$workerFunction = new $this->class();

		return $workerFunction;
	}

	/**
	 * 
	 */
	public function configure()
	{
		$workerFunction = $this->getWorkerFunctionInstance();

		$worker = $this->getWorker();

		$worker->addFunction($this->name, array($workerFunction, 'processJob'));
	}

}
