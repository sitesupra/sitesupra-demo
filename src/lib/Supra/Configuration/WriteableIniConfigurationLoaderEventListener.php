<?php

namespace Supra\Session;

use Supra\Controller\Event\FrontControllerShutdownEventArgs;
use \Supra\Configuration\Loader\WriteableIniConfigurationLoader;

class WriteableIniConfigurationLoaderEventListener
{

	/**
	 * @var WriteableIniConfigurationLoader
	 */
	protected $loader;

	/**
	 * @param WriteableIniConfigurationLoader $loader 
	 */
	function __construct(WriteableIniConfigurationLoader $loader)
	{
		$this->loader = $loader;
	}

	/**
	 * @param FrontControllerShutdownEventArgs $eventArgs 
	 */
	public function onFrontControlerShutdown(FrontControllerShutdownEventArgs $eventArgs)
	{
		$loader = $this->loader;

		$loader->write();
	}

}
