<?php

namespace Supra\Router;

use Supra\Request\RequestInterface;
use Supra\Controller\ControllerInterface;
use Closure;

/**
 * Router interface
 */
interface RouterInterface
{
	const CN = __CLASS__;
	
	/**
	 * Whether the router matches the request
	 * @param RequestInterface $request
	 */
    public function match(RequestInterface $request);
	
	/**
	 * Finalizes request and sets base path.
	 * Method is NOT called for prefilters.
	 * @param RequestInterface $request
	 */
	public function finalizeRequest(RequestInterface $request);

	/**
	 * Get router priority
	 * 1) Base priority
	 * 2) Local priority
	 * 3) Priority differnce
	 * @return array
	 */
	public function getPriority();
	
	/**
	 * Sets priority difference
	 */
	public function setPriorityDiff($priorityDiff);

	/**
	 * @return string
	 */
	public function getControllerClass();
	
	/**
	 * @return Closure
	 */
	public function getControllerClosure();
	
	/**
	 * Set closure which creates the controller to route
	 * @param Closure $controllerClosure
	 */
	public function setControllerClosure(Closure $controllerClosure);

	/**
	 * Set parameters
	 * @param array $parameters
	 */
	public function setParameters(array $parameters);

	/**
	 * Gets parameters
	 * @return array
	 */
	public function getParameters();

	/**
	 * Get parameter value
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function getParameter($key, $default = null);

	/**
	 * @return mixed
	 */
	public function getObjectRepositoryCaller();

	/**
	 * @param mixed $objectRepositoryCaller
	 */
	public function setObjectRepositoryCaller($objectRepositoryCaller);
	
	/**
	 * Represents the router as string
	 * @return string
	 */
	public function __toString();
}
