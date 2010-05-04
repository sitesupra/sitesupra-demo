<?php

namespace Supra\Controller\Router;

use Supra\Controller\ControllerInterface;
use Supra\Controller\Exception;

/**
 * Description of RouterAbstraction
 */
abstract class RouterAbstraction implements RouterInterface
{
	/**
	 * Controller class name
	 * @var string
	 */
	protected $controllerClass;

	/**
	 * Bound controller
	 * @var ControllerInterface
	 */
	protected $controller;

	/**
	 * Set controller to route
	 * @param string $controller
	 */
	public function setControllerClass($controllerClass)
	{
		if (empty($controllerClass)) {
			throw new Exception("Controller class name is not provided for the router");
		}
		$this->controllerClass = $controllerClass;
	}

	/**
	 * Get routed controller
	 * @return ControllerInterface
	 */
	public function getController()
	{
		if ( ! is_null($this->controller)) {
			return $this->controller;
		}
		if ( ! class_exists($this->controllerClass)) {
			throw new Exception("Controller class {$this->controllerClass} cannot be found");
		}
		$this->controller = new $this->controllerClass;
		if ( ! ($this->controller instanceof ControllerInterface)) {
			throw new Exception("Controller class {$this->controllerClass} does not implement Supra\\Controller\\ControllerInterface");
		}
		return $this->controller;
	}

}