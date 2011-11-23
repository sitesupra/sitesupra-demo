<?php

namespace Supra\Controller\Configuration;

use Supra\Router\Configuration\RouterConfiguration;
use Supra\Configuration\ConfigurationInterface;

/**
 * Controller base configuration
 */
class ControllerConfiguration implements ConfigurationInterface
{
	/**
	 * @var RouterConfiguration
	 */
	public $router;
	
	/**
	 * Controller configuration
	 */
	public function configure()
	{
		
	}
}
