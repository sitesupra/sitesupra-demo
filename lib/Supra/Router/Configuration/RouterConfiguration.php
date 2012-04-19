<?php

namespace Supra\Router\Configuration;

use Supra\Router\RouterInterface;
use Supra\Router\RouterAbstraction;
use Supra\Configuration\ConfigurationInterface;
use Supra\Loader\Loader;
use Supra\Router\UriRouter;
use Supra\Controller\FrontController;
use Supra\Controller\Configuration\ControllerConfiguration;
use Supra\Configuration\Exception\InvalidConfiguration;
use Supra\ObjectRepository\ObjectRepository;

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
	 * @var ControllerConfiguration
	 */
	public $controller;
	
	/**
     * Default Controller execution priority
     */
	public $priority = RouterAbstraction::PRIORITY_MEDIUM;
	
	/**
	 * @var boolean
	 */
	public $strictUrlMatch = false;
	
	/**
	 * What caller should be used to access object repository
	 * @var mixed
	 */
	public $objectRepositoryCaller = null;

	/**
	 * @return RouterInterface
	 */
	public function configure()
	{
		$router = Loader::getClassInstance($this->class, RouterInterface::CN);
		
		//TODO: should create some better parameter passing for different router implementations
		if ($router instanceof UriRouter) {
			$router->setPath($this->url);
			$router->setStrictUrlMatch($this->strictUrlMatch);
		}
		
		$router->setObjectRepositoryCaller($this->objectRepositoryCaller);
		$router->setPriorityDiff($this->priority);
		
		// TODO: remove after refactored
		if (is_string($this->controller)) {
			$class = $this->controller;
			$this->controller = new ControllerConfiguration();
			$this->controller->class = $class;
			
			ObjectRepository::getLogger($this)
					->warn("DEPRECATED: Router controller property must be ControllerConfiguration instance");
		}
		
		if ( ! $this->controller instanceof ControllerConfiguration) {
			throw new InvalidConfiguration("Not controller configuration attached to the router $this->class, on URL $this->url");
		}
		
		$closure = $this->controller->getClosure();

		// Bind to URL
		FrontController::getInstance()
					->route($router, $this->controller->class, $closure);
	}
	
}
