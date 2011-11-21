<?php

namespace Supra\Request;

use Supra\Uri\Path;
use Supra\Log\Log;

/**
 * Http request object
 */
class HttpRequest implements RequestInterface
{
	// Request method constants
	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';
	const METHOD_DELETE = 'DELETE';
	const METHOD_HEAD = 'HEAD';
	
	/**
	 * Server arguments
	 * @var array
	 */
	protected $server = array();

	/**
	 * GET parameters
	 * @var RequestData
	 */
	protected $query;

	/**
	 * POST data
	 * @var RequestData
	 */
	protected $post;

	/**
	 * Cookies received from the client
	 * @var array
	 */
	protected $cookies = array();

	/**
	 * Full request URI
	 * @var string
	 */
	protected $requestUri;

	/**
	 * Path remainder used by controller
	 * @var Path
	 */
	protected $path;
	
	/**
	 * Set empty POST/GET
	 */
	public function __construct()
	{
		$this->post = new RequestData();
		$this->query = new RequestData();
	}

	/**
	 * {@inheritdoc}
	 */
	public function readEnvironment()
	{
		if (isset($_SERVER)) {
			$this->setServer($_SERVER);
		}
		if (isset($_GET)) {
			$this->setQuery($_GET);
		}
		if (isset($_POST)) {
			$this->setPost($_POST);
		}
		if (isset($_COOKIE)) {
			$this->setCookies($_COOKIE);
		}

		if ( ! isset($_SERVER['SCRIPT_URL'])) {
			throw new Exception\InvalidRequest("Script URL not set in Http request object");
		}

		$this->requestUri = $_SERVER['SCRIPT_URL'];
		Log::info('Request URI: ', $this->requestUri);

		$path = new Path($this->requestUri);
		$this->setPath($path);
	}

	/**
	 *
	 * @return string 
	 */
	public function getRequestUri()
	{
		return $this->requestUri;
	}

	/**
	 * Get action list
	 * @param integer $limit
	 * @return string[]
	 */
	public function getActions($limit = null)
	{
		$actions = $this->path->getPathList();
		
		if ($limit > 0) {
			return array_slice($actions, 0, $limit);
		} else {
			return $actions;
		}
	}

	/**
	 * Get request parameter from GET
	 * @param string $key
	 * @param string $default
	 * @return string
	 */
	public function getParameter($key, $default = null)
	{
		return $this->getQueryValue($key, $default);
	}

	/**
	 * Sets server data array
	 * @param array $server
	 */
	public function setServer($server)
	{
		$this->server = $server;
	}

	/**
	 * Get server data array
	 * @return array
	 */
	public function getServer()
	{
		return $this->server;
	}

	/**
	 * Get server data value
	 * @param string $key
	 * @param string $default
	 * @return string
	 */
	public function getServerValue($key, $default = null)
	{
		if ( ! array_key_exists($key, $this->server)) {
			return $default;
		}
		
		return $this->server[$key];
	}

	/**
	 * Set GET data array
	 * @param array $query
	 */
	public function setQuery($query)
	{
		$this->query = new RequestData($query);
	}

	/**
	 * Get GET data array
	 * @return RequestData
	 */
	public function getQuery()
	{
		return $this->query;
	}

	/**
	 * Get GET data value
	 * @param string $index
	 * @param string $default
	 * @return string
	 */
	public function getQueryValue($index, $default = null)
	{
		if ( ! $this->query->offsetExists($index)) {
			return $default;
		}
		
		return $this->query[$index];
	}

	/**
	 * Set POST data array
	 * @param array $post
	 */
	public function setPost($post)
	{
		$this->post = new RequestData($post);
	}

	/**
	 * Get POST data array
	 * @return RequestData
	 */
	public function getPost()
	{
		return $this->post;
	}

	/**
	 * Get POST data value
	 * @param string $index
	 * @param string $default
	 * @return string
	 */
	public function getPostValue($index, $default = null)
	{
		if ( ! $this->post->offsetExists($index)) {
			return $default;
		}
		
		return $this->post[$index];
	}

	/**
	 * Gets cookies
	 * @return array
	 */
	public function getCookies()
	{
		return $this->cookies;
	}

	/**
	 * Sets cookies
	 * @param array $cookies
	 */
	public function setCookies(array $cookies)
	{
		$this->cookies = $cookies;
	}

	/**
	 * Get cookie parameter
	 * @param string $key
	 * @param string $default
	 * @return string
	 */
	public function getCookie($key, $default = null)
	{
		if ( ! array_key_exists($key, $this->cookies)) {
			return $default;
		}
		
		return $this->cookies[$key];
	}

	/**
	 * Set path parameter
	 * @param Path $path
	 */
	public function setPath(Path $path)
	{
		$this->path = $path;
	}

	/**
	 * Get path parameter
	 * @return Path
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * If the request was a post request
	 * @return boolean
	 */
	public function isPost()
	{
		$requestMethod = $this->getServerValue('REQUEST_METHOD');
		$isPost = $requestMethod == self::METHOD_POST;
		
		return $isPost;
	}

	/**
	 * If the request was a get request
	 * @return boolean
	 */
	public function isGet()
	{
		$requestMethod = $this->getServerValue('REQUEST_METHOD');
		$isGet = $requestMethod == self::METHOD_GET;
		
		return $isGet;
	}

	/**
	 * Get all actions as string joined by $glue argument value
	 * @param string $glue
	 * @return string
	 */
	public function getActionString($glue = '/')
	{
		$previousSeparator = $this->path->getSeparator();
		$this->path->setSeparator($glue);
		$path = $this->path->getPath();
		$this->path->setSeparator($previousSeparator);
		
		return $path;
	}
}