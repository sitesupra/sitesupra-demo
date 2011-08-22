<?php

namespace Supra\Controller\Configuration;

use Supra\Controller\FrontController;
use Supra\Loader\Registry;
use Supra\Loader\Configuration\NamespaceConfiguration;
use Supra\Router\Configuration\RouterConfiguration;

/**
 * Controller base configuration
 */
class ControllerConfiguration implements ConfigurationInterface
{
	/**
	 * @var NamespaceConfiguration
	 */
	public $namespace;
	
	/**
	 * @var RouterConfiguration
	 */
	public $router;
	
	/**
	 * Controller configuration
	 */
	public function configure()
	{
		if (isset($this->namespace)) {
			// Register namespace
			$namespace = $this->namespace->configure();
			Registry::getInstance()->registerNamespace($namespace);
		}

		// Bind to URL
		if (isset($this->router)) {
			$router = $this->router->configure();
			FrontController::getInstance()->route($router, $this->router->controller);
		}
	}
}
