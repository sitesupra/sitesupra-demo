<?php

namespace Supra\Router;

use Supra\Controller\ControllerInterface;
use Supra\Request\RequestInterface;
use Supra\Loader\Loader;


/**
 * Description of RouterAbstraction
 */
abstract class RouterAbstraction implements RouterInterface
{
	/**
	 * Controller execution priority levels
	 */

	const PRIORITY_VERY_LOW = 10;
	const PRIORITY_LOW = 20;
	const PRIORITY_MEDIUM = 30;
	const PRIORITY_HIGH = 40;
	const PRIORITY_TOP = 50;

	/**
	 * Default router parameters
	 * @var array
	 */
	static protected $defaultParameters = array();

	/**
	 * Router parameters
	 * @var array
	 */
	protected $parameters = array();

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
	 * Controlls order for routers with equal base and router calculated priority
	 * @var integer
	 */
	protected $priorityDiff;
	
	/**
	 * @return integer 
	 */
	public function getPriorityDiff()
	{
		return $this->priorityDiff;
	}
	
	/**
	 * @param integer $priorityDiff 
	 */
	public function setPriorityDiff($priorityDiff)
	{
		$this->priorityDiff = $priorityDiff;
	}

	/**
	 * Set controller to route
	 * @param string $controller
	 */
	public function setControllerClass($controllerClass)
	{
		if (empty($controllerClass)) {
			throw new Exception\RuntimeException("Controller class name is not provided for the router");
		}
		$this->controllerClass = $controllerClass;
	}
	
	/**
	 * @return string
	 */
	public function getControllerClass()
	{
		return $this->controllerClass;
	}

//	/**
//	 * {@inheritdoc}
//	 * @return ControllerInterface
//	 */
//	public function initializeController()
//	{
//		if ( ! is_null($this->controller)) {
//			throw new Exception\RuntimeException("Controller is already initialized");
//		}
//		
//		if ( ! class_exists($this->controllerClass)) {
//			throw new Exception\RuntimeException("Controller class {$this->controllerClass} cannot be found");
//		}
//		
//		$this->controller = Loader::getClassInstance($this->controllerClass, 'Supra\Controller\ControllerInterface');
//		
//		return $this->controller;
//	}
//	
//	/**
//	 * {@inheritdoc}
//	 * @return ControllerInterface
//	 */
//	public function getController()
//	{
//		if (empty($this->controller)) {
//			throw new Exception\RuntimeException("Controller not initialized");
//		}
//		
//		return $this->controller;
//	}

	/**
	 * Set parameters
	 * @param array $parameters
	 */
	public function setParameters(array $parameters)
	{
		$this->parameters = static::$defaultParameters + $parameters;
	}

	/**
	 * Gets parameters
	 * @return array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * Get parameter value
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function getParameter($key, $default = null)
	{
		if (array_key_exists($key, $this->parameters)) {
			return $this->parameters[$key];
		}
		
		return $default;
	}
	
	/**
	 * {@inheritdoc}
	 * @param RequestInterface $request
	 */
	public function finalizeRequest(RequestInterface $request)
	{
		
	}

}