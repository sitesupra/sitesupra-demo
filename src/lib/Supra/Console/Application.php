<?php

namespace Supra\Console;

use Supra\ObjectRepository\ObjectRepository;
use Symfony\Component\Console\Application as SymfonyConsoleApplication;
use Supra\Console\Cron\Period\AbstractPeriod;
use Supra\Loader\Loader;

/**
 * Application
 *
 */
class Application extends SymfonyConsoleApplication
{

	/**
	 * @var array
	 */
	protected $jobCollection = array();
	
	/**
	 * Instance
	 *
	 * @var Application
	 */
	private static $instance;

	/**
	 * Constructor
	 * 
	 */
	public function __construct()
	{
		$info = ObjectRepository::getSystemInfo($this);
		parent::__construct($info->name, $info->version);
	}

	/**
	 * Get instance
	 *
	 * @return Application
	 */
	public static function getInstance()
	{
		if ( ! self::$instance instanceof Application) {
			self::$instance = new Application();
		}
		return self::$instance;
	}

	/**
	 * Add cron job
	 * 
	 * @param string $input
	 * @param AbstractPeriod $period
	 */
	public function addCronJob($input, AbstractPeriod $period)
	{
		$em = ObjectRepository::getEntityManager($this);
		$repo = $em->getRepository(Cron\Entity\CronJob::CN());
		/* @var $repo CronJobRepository */
		
		$jobEntity = $repo->addJob($input, $period);
		
		$this->jobCollection[$jobEntity->getId()] = $jobEntity;
	}
	
	/**
	 * @return array
	 */
	public function getDefinedCronJobs()
	{
		return $this->jobCollection;
	}

	/**
	 * Command line application commands can be passed as classnames.
	 * This allows skipping non-existent classes.
	 * @param array|string $classes
	 */
	public function addCommandClasses($classes)
	{
		$classes = (array) $classes;
		$commands = array();

		foreach ($classes as $class) {
			if (Loader::classExists($class)) {
				$commands[] = new $class();
			}
		}

		$this->addCommands($commands);
	}

	/**
	 *
	 * @param array $classes 
	 */
	public function setCommandClasses($classes)
	{
		$classes = (array) $classes;
		$commands = array();

		foreach ($classes as $class) {
			if (Loader::classExists($class)) {
				$commands[] = new $class();
			}
		}
	}

}
