<?php

namespace Supra\Console;

use Supra\Version;
use Symfony\Component\Console\Application as SymfonyConsoleApplication;

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
	
}
