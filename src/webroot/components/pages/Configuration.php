<?php

namespace Project\Pages;

use Supra\Controller\Pages;
use Supra\Controller\ConfigurationInterface;
use Supra\Controller\FrontController;
use Supra\Loader\Registry;
use Supra\Loader\NamespaceRecord;
use Supra\Router\UriRouter;

class Configuration implements ConfigurationInterface
{
	/**
	 * Controller configuration
	 * @param FrontController $frontController
	 * @param Registry $registry 
	 */
	public function configure(FrontController $frontController = null,
			Registry $registry = null)
	{
		// Register namespace
		$namespace = new NamespaceRecord(__NAMESPACE__, __DIR__);
		$registry->registerNamespace($namespace);

		// Bind to URL
		$router = new UriRouter('/');
		$frontController->route($router, '\\Project\\Pages\\PageController');
	}
}
