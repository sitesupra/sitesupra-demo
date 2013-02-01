<?php

namespace Supra\RemoteHttp\Request;

class RemoteHttpRequest
{
	const TYPE_GET = 'GET';
	const TYPE_POST = 'POST';
	const TYPE_PUT = 'PUT';
	const TYPE_DELETE = 'DELETE';
	
	/**
	 * @var string
	 */
	protected $requestUrl;
	
	/**
	 * @var array
	 */
	protected $headers = array();
	
	/**
	 * @var array
	 */
	protected $cookies = array();

	/**
	 * @var string
	 */
	protected $type = self::TYPE_GET;
	
	protected $params = array();
	
	
	public function __construct($requestUrl, $type = null, $params = array())
	{
		if ( ! is_null($type)) {
			$this->setType($type);
		}
		
		$this->requestUrl = $requestUrl;
		$this->params = $params;
	}
	
	public function setType($type)
	{
		$this->type = $type;
	}
	
	public function header($name, $value, $force = false)
	{
		if (isset($this->headers[$name]) && ! $force) {
			return false;
		}
		
		$this->headers[$name] = $value;
	}
	
	/**
	 * @return string
	 */
	public function getUrl()
	{
		return $this->requestUrl;
	}
	
	/**
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->headers;
	}
	
	public function getType()
	{
		return $this->type;
	}
	
	public function getRequestParameters()
	{
		return $this->params;
	}
}