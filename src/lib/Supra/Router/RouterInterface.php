<?php

namespace Supra\Router;

use Supra\Request\RequestInterface;
use Supra\Controller\ControllerInterface;

/**
 * Router interface
 */
interface RouterInterface
{
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
	 * Set controller to route
	 * @param string $controllerClass
	 */
	public function setControllerClass($controllerClass);

//	/**
//	 * Initialize routed controller
//	 * @return ControllerInterface
//	 */
//	public function initializeController();
//	
//	/**
//	 * Get routed controller
//	 * @return ControllerInterface
//	 */
//	public function getController();

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
	 * Represents the router as string
	 * @return string
	 */
	public function __toString();
}