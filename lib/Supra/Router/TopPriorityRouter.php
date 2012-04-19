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
	 * {@inheritdoc}
	 * @return array
	 */
	public function getPriority()
	{
		$priority = array(static::$basePriority, 0, $this->priorityDiff);
		
		return $priority;
	}

	/**
	 * Represents the router as string
	 * @return string
	 */
	public function __toString()
	{
		return __CLASS__;
	}
}