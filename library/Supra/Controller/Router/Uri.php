<?php

namespace Supra\Controller\Router;

use Supra\Controller\Request\RequestInterface;
use Supra\Controller\Request\Http;

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
	 * @var string
	 */
	protected $uri;

	/**
	 * URI depth
	 * @var integer
	 */
	protected $depth;

	/**
	 * Router constructor
	 * @param string $uri
	 * @param array $params
	 */
	public function __construct($uri, array $params = array())
	{
		$this->uri = trim($uri, '/');
		$this->depth = substr_count($this->uri, '/');
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
		if ( ! ($request instanceof Http)) {
			\Log::sdebug('Not the instance of Request\\Http');
			return false;
		}
		$path = $request->getPath();
		$uriLength = strlen($this->uri);
		if (substr($path, 0, $uriLength) == $this->uri) {
			$request->setBasePath($this->uri);
			$request->setPath(substr($path, $uriLength));
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
		return static::$basePriority + $this->depth;
	}

	/**
	 * Represents the router as string
	 * @return string
	 */
	public function __toString()
	{
		return __CLASS__ . ':' . $this->uri;
	}
}