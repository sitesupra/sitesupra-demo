<?php

namespace Supra\Router;

use Supra\Controller\ControllerInterface;
use Supra\Request\RequestInterface;
use Supra\Loader\Loader;
use Closure;

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
	 * @var string
	 */
	protected $controllerClass;
	
	/**
	 * Controller creation closure
	 * @var Closure
	 */
	protected $controllerClosure;

	/**
	 * Controlls order for routers with equal base and router calculated priority
	 * @var integer
	 */
	protected $priorityDiff;
	
	/**
	 * What caller should be used to access object repository
	 * @var mixed
	 */
	protected $objectRepositoryCaller;
	
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
	 * @param Closure $controllerClosure
	 */
	public function setControllerClosure(Closure $controllerClosure = null)
	{
		$this->controllerClosure = $controllerClosure;
	}
	
	/**
	 * @return Closure
	 */
	public function getControllerClosure()
	{
		return $this->controllerClosure;
	}
	
	/**
	 * @return string
	 */
	public function getControllerClass()
	{
		return $this->controllerClass;
	}
	
	/**
	 * @param string $controllerClass
	 */
	public function setControllerClass($controllerClass)
	{
		$this->controllerClass = $controllerClass;
	}

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

	/**
	 * @return mixed
	 */
	public function getObjectRepositoryCaller()
	{
		return $this->objectRepositoryCaller;
	}

	/**
	 * @param mixed $objectRepositoryCaller
	 */
	public function setObjectRepositoryCaller($objectRepositoryCaller)
	{
		$this->objectRepositoryCaller = $objectRepositoryCaller;
	}
}
