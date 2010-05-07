<?php

namespace Supra\Controller\Router;

use Supra\Controller\Request\RequestInterface;
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
	 * Get router priority
	 * @return integer
	 */
	public function getPriority();

	/**
	 * Set controller to route
	 * @param string $controllerClass
	 */
	public function setControllerClass($controllerClass);

	/**
	 * Get routed controller
	 * @return ControllerInterface
	 */
	public function getController();

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