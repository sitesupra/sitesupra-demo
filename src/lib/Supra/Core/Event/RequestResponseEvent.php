<?php

namespace Supra\Core\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestResponseEvent extends Event
{
	/**
	 * @var \Symfony\Component\HttpFoundation\Request
	 */
	protected $request;

	/**
	 * @var \Symfony\Component\HttpFoundation\Response
	 */
	protected $response;

	/**
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 */
	public function setRequest(Request $request)
	{
		$this->request = $request;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @param \Symfony\Component\HttpFoundation\Response $response
	 */
	public function setResponse(Response $response)
	{
		$this->response = $response;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * @return bool
	 */
	public function hasRequest()
	{
		return (bool)$this->request;
	}

	/**
	 * @return bool
	 */
	public function hasResponse()
	{
		return (bool)$this->response;
	}
}