<?php

namespace Supra\Router\Configuration;

use Supra\Router\RouterInterface;
use Supra\Router\RouterAbstraction;
use Supra\Configuration\ConfigurationInterface;
use Supra\Loader\Loader;
use Supra\Router\UriRouter;
use Supra\Controller\FrontController;

/**
 * RouterConfiguration
 */
class RouterConfiguration implements ConfigurationInterface
{
	/**
	 * @var string
	 */
	public $class = 'Supra\Router\UriRouter';
	
	/**
	 * @var string
	 */
	public $url = '/';
	
	/**
	 * @var string
	 */
	public $controller;
	
	/**
     * Default Controller execution priority
     */
	public $priority = RouterAbstraction::PRIORITY_MEDIUM;
	
	/**
	 * @return RouterInterface
	 */
	public function configure()
	{
		$router = Loader::getClassInstance($this->class, RouterInterface::CN);
		
		//TODO: should create some better parameter passing for different router implementations
		if ($router instanceof UriRouter) {
			$router->setPath($this->url);
		}
		
		$router->setPriorityDiff($this->priority);
		
		// Bind to URL
		FrontController::getInstance()
					->route($router, $this->controller);
	}
	
}
