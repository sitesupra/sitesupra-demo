<?php

namespace Supra\Console;

use Supra\Version;
use Supra\ObjectRepository\ObjectRepository;
use Symfony\Component\Console\Application as SymfonyConsoleApplication;
use Supra\Component\Console\Cron\Period\AbstractPeriod;

/**
 * Application
 *
 */
class Application extends SymfonyConsoleApplication
{

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
		parent::__construct('Supra Command Line Interface', Version::VERSION);
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
		$repo->addJob($input, $period);
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
			if (class_exists($class)) {
				$commands[] = new $class();
			}
		}
		
		$this->addCommands($commands);
	}
	
}
