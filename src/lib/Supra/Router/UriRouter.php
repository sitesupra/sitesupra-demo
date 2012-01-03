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

	protected $strictUrlMatch = false;
	
	/**
	 * Router constructor
	 */
	public function __construct()
	{
		$this->path = new Path();
	}
	
	/**
	 * Sets the path
	 * @param mixed $path
	 */
	public function setPath($path)
	{
		if ($path instanceof Path) {
			$this->path = $path;
		} else {
			$this->path = new Path($path);
		}
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

		if ($this->isStrictUrlMatch()) {
			if ($path->equals($this->path)) {
				return true;
			}
			
			return false;
		}

		if ($path->startsWith($this->path)) {
//			$path->setBasePath($this->path);

			return true;
		}

		return false;
	}
	
	/**
	 * {@inheritdoc}
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
	 * @return boolean 
	 */
	public function isStrictUrlMatch()
	{
		return $this->strictUrlMatch;
	}

	/**
	 * @param boolean $strictUrlMatch 
	 */
	public function setStrictUrlMatch($strictUrlMatch)
	{
		$this->strictUrlMatch = $strictUrlMatch;
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