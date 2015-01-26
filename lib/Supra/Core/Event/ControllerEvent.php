<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

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
