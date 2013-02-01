<?php

namespace Supra\RemoteHttp\Response;

class RemoteHttpResponse
{
	/**
	 * @var integer
	 */
	protected $code;
	
	/**
	 * @var string
	 */
	protected $body;
	
	/**
	 * @var array
	 */
	protected $headers = array();
	
	/**
	 * @var array
	 */
	protected $cookies = array();
	
	/**
	 * @return integer
	 */
	public function getCode()
	{
		return $this->code;
	}
	
	/**
	 * @return string
	 */
	public function getBody()
	{
		return $this->body;
	}
	
	/**
	 * @param string $name
	 * @return string
	 */
	public function getHeader($name)
	{
		if (isset($this->headers[$name])) {
			return $this->headers[$name];
		}
		
		return null;
	}
	
	/**
	 * @return array
	 */
	public function getHeadersArray()
	{
		return $this->headers;
	}
	
	public function getCookie($name)
	{
		throw new \Exception('Not implemented');
	}
	
	public function setHttpCode($code)
	{
		$this->code = $code;
	}
	
	public function setBody($body)
	{
		$this->body = $body;
	}
	
}