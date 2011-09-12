<?php

namespace Supra\Router;

use Supra\Request\RequestInterface;
use Supra\Request\HttpRequest;
use Supra\Uri\Path;

/**
 * Top Priority Router
 */
class TopPriorityRouter extends RouterAbstraction
{
	/**
	 * We can't allow to set base path multiple times for the request in case of chained controllers
	 * @var boolean
	 */
	protected static $requestFinalized = false;
	
	/**
	 * Base priority value
	 * @var integer
	 */
	protected static $basePriority = 1000;
	
	/**
	 * URI of the router what binds it to controller
	 * @var Path
	 */
	protected $path;

	/**
	 * Router constructor
	 * @param string $uri
	 * @param array $params
	 */
	public function __construct()
	{
	}

	/**
	 * Whether the router matches the request.
	 * Additionally move used URI part to the base URI property.
	 * @param RequestInterface $request
	 * @return boolean
	 */
	public function match(RequestInterface $request)
	{
		// will match always
		return true;
	}
	
	/**
	 * Finalizes request and sets base path
	 * @param RequestInterface $request
	 */
	public function finalizeRequest(RequestInterface $request)
	{
		// empty
	}

	/**
	 * Get router priority
	 * @return array
	 */
	public function getPriority()
	{
		$priority = array(static::$basePriority, 0, 0);
		return $priority;
	}

	/**
	 * Represents the router as string
	 * @return string
	 */
	public function __toString()
	{
		return __CLASS__ . ':' . $this->path->__toString();
	}
}