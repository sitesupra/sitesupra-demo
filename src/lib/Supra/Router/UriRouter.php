<?php

namespace Supra\Router;

use Supra\Request\RequestInterface;
use Supra\Request\HttpRequest;
use Supra\Uri\Path;

/**
 * URI router
 */
class UriRouter extends RouterAbstraction
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
	protected static $basePriority = 100;
	
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
	public function __construct($uri, array $params = array())
	{
		$this->path = new Path($uri);
		$this->setParameters($params);
	}

	/**
	 * Whether the router matches the request.
	 * Additionally move used URI part to the base URI property.
	 * @param RequestInterface $request
	 * @return boolean
	 */
	public function match(RequestInterface $request)
	{
		if ( ! ($request instanceof HttpRequest)) {
			\Log::debug('Not the instance of Request\HttpRequest');
			return false;
		}

		$path = $request->getPath();

		if ($path->startsWith($this->path)) {
//			$path->setBasePath($this->path);
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Finalizes request and sets base path
	 * @param RequestInterface $request
	 */
	public function finalizeRequest(RequestInterface $request)
	{
		if (self::$requestFinalized) {
			return;
		}
		
		if ( ! ($request instanceof HttpRequest)) {
			\Log::debug('Not the instance of Request\HttpRequest');
			return;
		}
		
		$path = $request->getPath();
		$path->setBasePath($this->path);
		self::$requestFinalized = true;
	}

	/**
	 * Get router priority
	 * @return array
	 */
	public function getPriority()
	{
		$priority = array(static::$basePriority, $this->path->getDepth(), $this->priorityDiff);
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