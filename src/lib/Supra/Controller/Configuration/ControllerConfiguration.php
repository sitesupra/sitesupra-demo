<?php

namespace Supra\Controller\Configuration;

use Supra\Controller\FrontController;
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
		// Bind to URL
		if (isset($this->router)) {
			$router = $this->router->configure();
			FrontController::getInstance()->route($router, $this->router->controller);
		}
	}
}
