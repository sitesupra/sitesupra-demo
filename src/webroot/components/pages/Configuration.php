<?php

namespace Project\Pages;

use Supra\Controller\Pages;
use Supra\Controller\Configuration\ConfigurationInterface;
use Supra\Controller\FrontController;
use Supra\Loader\Registry;
use Supra\Loader\NamespaceRecord;
use Supra\Router\UriRouter;

class Configuration implements ConfigurationInterface
{
	/**
	 * @var NamespaceConfiguration
	 */
	public $namespace;
	
	/**
	 * @var RouterConfiguration
	 */
	public $router;
	
	public function __construct()
	{
		$this->namespace = new NamespaceConfiguration();
		$this->router = new RouterConfiguration();
	}
	
	/**
	 * Controller configuration
	 * @param FrontController $frontController
	 * @param Registry $registry
	 */
	public function configure(FrontController $frontController = null,
			Registry $registry = null)
	{
		if (isset($this->namespace)) {
			// Register namespace
			$namespace = $this->namespace->configure();
			$registry->registerNamespace($namespace);
		}

		// Bind to URL
		if (isset($this->router)) {
			$router = $this->router->configure();
			$frontController->route($router, $this->router->controller);
		}
	}
}

/**
 * NamespaceConfiguration
 */
class NamespaceConfiguration
{
	public $class;
	
	public $namespace = __NAMESPACE__;
	
	public $dir = __DIR__;
	
	public function configure()
	{
		$namespaceRecord = new $this->class($this->namespace, $this->dir);
		
		return $namespaceRecord;
	}
}

/**
 * RouterConfiguration
 */
class RouterConfiguration
{
	public $class = 'Supra\Router\UriRouter';
	
	public $url = '/';
	
	public $controller = 'Project\Pages\PageController';
	
	public function configure()
	{
		$router = new $this->class($this->url);
		
		return $router;
	}
}
