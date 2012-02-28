<?php

namespace Supra\Controller\Configuration;

use Supra\Router\Configuration\RouterConfiguration;
use Supra\Configuration\ConfigurationInterface;
use Closure;

/**
 * Controller base configuration
 */
class ControllerConfiguration implements ConfigurationInterface
{
	/**
	 * @var RouterConfiguration
	 * @TODO: remove
	 */
	public $router;
	
	/**
	 * @var string
	 */
	public $class;
	
	/**
	 * Controller configuration
	 */
	public function configure()
	{
		if ( ! empty($this->router)) {
			ObjectRepository::getLogger($this)
					->warn('DEPRECATED: Configuration binding ControllerConfiguration->RouterConfiguration will be removed, please update to reverse configuration RouterConfiguration->ControllerConfiguration');
		}
	}
	
	/**
	 * Might return closure if the controller instance needs to be configured additionally
	 * @return Closure
	 */
	public function getClosure()
	{
		return null;
	}
}
