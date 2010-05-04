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
	 * Represents the router as string
	 * @return string
	 */
	public function __toString();
}