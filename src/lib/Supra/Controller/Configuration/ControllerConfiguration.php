<?php

namespace Supra\Controller\Configuration;

use Supra\Controller\FrontController;
use Supra\Loader\Registry;
use Supra\Loader\Configuration\NamespaceConfiguration;
use Supra\Router\Configuration\RouterConfiguration;

/**
 * Controller base configuration
 */
class ControllerConfiguration implements ControllerConfigurationInterface
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
