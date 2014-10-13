<?php

namespace Supra\Core\Event;

use Supra\Core\Controller\Controller;
use Symfony\Component\EventDispatcher\Event;

class ControllerEvent extends Event
{
	/**
	 * @var Controller
	 */
	protected $controller;

	/**
	 * @var mixed
	 */
	protected $response;

	/**
	 * @var string
	 */
	protected $action;

	/**
	 * @return string
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @param string $action
	 */
	public function setAction($action)
	{
		$this->action = $action;
	}

	/**
	 * @return Controller
	 */
	public function getController()
	{
		return $this->controller;
	}

	/**
	 * @param Controller $controller
	 */
	public function setController($controller)
	{
		$this->controller = $controller;
	}


	/**
	 * @return mixed
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * @param mixed $response
	 */
	public function setResponse($response)
	{
		$this->response = $response;
	}

}
