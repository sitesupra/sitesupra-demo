<?php

namespace Supra\Console\Gearman;

use Supra\ObjectRepository\ObjectRepository;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Supra\Configuration\Loader\IniConfigurationLoader;
use Symfony\Component\Console\Input\InputOption;
use Supra\Configuration\Parser\YamlParser;

/**
 * Gearman worker command
 */
class WorkerCommand extends SymfonyCommand
{

	/**
	 * @var IniConfigurationLoader
	 */
	protected $iniConfiguration;

	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $maxJobs = false;

	/**
	 * @var \GearmanWorker
	 */
	protected $worker;

	/**
	 * @return IniConfigurationLoader
	 */
	public function getIniConfiguration()
	{
		if (empty($this->iniConfiguration)) {
			$this->iniConfiguration = ObjectRepository::getIniConfigurationLoader($this);
		}

		return $this->iniConfiguration;
	}

	/**
	 * Configure
	 */
	protected function configure()
	{
		$this->setName('su:gearman:worker')
				->addOption('id', null, InputOption::VALUE_REQUIRED)
				->addOption('max-jobs', null, InputOption::VALUE_REQUIRED)
				->setDescription('Gearman worker.');
	}

	/**
	 * 
	 * @return \GearmanWorker
	 * @throws Exception\RuntimeExceptoion
	 */
	protected function getWorker()
	{
		if (empty($this->worker)) {

			$gearmanSection = $this->getIniConfiguration()->getSection('gearman');

			if (empty($gearmanSection)) {
				throw new Exception\RuntimeExceptoion('Gearman configuration not present.');
			}

			$worker = new \GearmanWorker();
			$worker->addServer($gearmanSection['host']);

			$this->worker = $worker;
		}



		return $this->worker;
	}

	protected function configureWorker()
	{
		$worker = $this->getWorker();

		$yamlParser = new YamlParser();
		$configurationLoader = new WorkerConfigurationLoader();
		$configurationLoader->setParser($yamlParser);
		$configurationLoader->setWorker($worker);
		$configurationLoader->setCacheLevel(WorkerConfigurationLoader::CACHE_LEVEL_NO_CACHE);

		$configurationLoader->loadFile(SUPRA_CONF_PATH . 'gearman-worker.yml');
	}

	/**
	 * Execute
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output 
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->id = $input->getOption('id');

		$this->configureWorker();
		//$worker->addFunction('makeLocalizationPreviewForSite', array($this, 'makeLocalizationPreview'));

		$this->runWorkerJobs(10);
	}

	/**
	 * 
	 */
	protected function runWorkerJobs()
	{
		$worker = $this->getWorker();

		$jobCounter = 1;

		while ($worker->work()) {

			\Log::debug('[' . $this->id . '] Working ... (' . $jobCounter . ')');

			if (GEARMAN_SUCCESS != $worker->returnCode()) {
				\Log::error('[' . $this->id . '] Worker failed: ' . $worker->error() . "\n");
			}

			$jobCounter ++;

			if ($this->maxJobs !== false && $jobCounter == $this->maxJobs) {
				break;
			}
		}
		\Log::debug('[' . $this->id . '] END.');
	}

	/**
	 * 
	 */
	protected function setupGearmanFunctions()
	{
		
	}

}
