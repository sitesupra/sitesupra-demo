<?php

namespace Supra\Controller\Request;

/**
 * Http request object
 */
class Http implements RequestInterface
{
	/**
	 * Server arguments
	 * @var array
	 */
	protected $server;

	/**
	 * GET parameters
	 * @var array
	 */
	protected $query;

	/**
	 * POST array
	 * @var array
	 */
	protected $post;

	/**
	 * Full request URI
	 * @var string
	 */
	protected $requestUri;

	/**
	 * Base path used to route the controller
	 * @var string
	 */
	protected $basePath;

	/**
	 * Path remainder used by controller
	 * @var string
	 */
	protected $path;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->setServer($_SERVER);
		$this->setQuery($_GET);
		$this->setPost($_POST);
		
		$this->requestUri = $_SERVER['SCRIPT_URL'];
		\Log::sinfo('Request URI: ', $this->requestUri);
		$this->setBasePath('');
		$this->setPath($this->requestUri);
	}

	/**
	 * Get action list
	 * @param integer $limit
	 * @return string[]
	 */
	public function getAction($limit = 1)
	{
		$path = $this->getPath();
		if (empty($path)) {
			return array();
		}
		$actions = explode('/', $path);
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
		$this->query = $query;
	}

	/**
	 * Get GET data array
	 * @return array
	 */
	public function getQuery()
	{
		return $this->query;
	}

	/**
	 * Get GET data value
	 * @param string $key
	 * @param string $default
	 * @return string
	 */
	public function getQueryValue($key, $default = null)
	{
		if ( ! array_key_exists($key, $this->query)) {
			return $default;
		}
		return $this->query[$key];
	}

	/**
	 * Set POST data array
	 * @param array $post
	 */
	public function setPost($post)
	{
		$this->post = $post;
	}

	/**
	 * Get POST data array
	 * @return array
	 */
	public function getPost()
	{
		$this->post;
	}

	/**
	 * Get POST data value
	 * @param string $key
	 * @param string $default
	 * @return string
	 */
	public function getPostValue($key, $default = null)
	{
		if ( ! array_key_exists($key, $this->post)) {
			return $default;
		}
		return $this->post[$key];
	}

	/**
	 * Set path paraeter
	 * @param string $path
	 */
	public function setPath($path)
	{
		$this->path = trim($path, '/');
	}

	/**
	 * Get path parameter
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Set base path parameter
	 * @param string $basePath
	 */
	public function setBasePath($basePath)
	{
		$this->basePath = '/' . trim($basePath, '/');
	}

	/**
	 * Get base path argument
	 * @return string
	 */
	public function getBasePath()
	{
		return $this->basePath;
	}

	/**
	 * If the request was a post request
	 * @return boolean
	 */
	public function isPost()
	{
		$requestMethod = $this->getServerValue('REQUEST_METHOD');
		return $requestMethod == 'POST';
	}

	/**
	 * If the request was a get request
	 * @return boolean
	 */
	public function isGet()
	{
		$requestMethod = $this->getServerValue('REQUEST_METHOD');
		return $requestMethod == 'GET';
	}
}