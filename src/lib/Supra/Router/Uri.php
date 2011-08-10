<?php

namespace Supra\Router;

use Supra\Request\RequestInterface;
use Supra\Request\HttpRequest;
use Supra\Uri\Path;

/**
 * URI router
 */
class Uri extends RouterAbstraction
{
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
			\Log::sdebug('Not the instance of Request\HttpRequest');
			return false;
		}

		$path = $request->getPath();

		if ($path->startsWith($this->path)) {
			$path->setBasePath($this->path);
			
			return true;
		}
		
		return false;
	}

	/**
	 * Get router priority
	 * @return integer
	 */
	public function getPriority()
	{
		return static::$basePriority + $this->path->getDepth();
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