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

namespace Supra\Core\Controller;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller implements ContainerAware
{
	/**
	 * DI container
	 *
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * Current package class name
	 *
	 * @var string
	 */
	protected $package;

	/**
	 * @var string
	 */
	protected $application;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;

		$this->setApplication();
	}

	public function renderResponse($template, $parameters = array())
	{
		$response = new Response();

		$response->setContent($this->render($template, $parameters));

		return $response;
	}

	public function render($template, $parameters)
	{
		if (strpos($template, ':') === false) {
			//there is no package name, we should add it
			$template = $this->getPackageName() . ':' . $template;
		}

		return $this->container->getTemplating()
			->render($template, $parameters);
	}

	protected function setApplication()
	{
		if ($this->application) {
			$this->container->getApplicationManager()->selectApplication($this->application);
		}
	}

	protected function getUser()
	{
		$context = $this->container->getSecurityContext();

		if ($context) {
			$token = $context->getToken();

			if ($token) {
				$user = $token->getUser();

				return $user;
			}
		}

		return false;
	}

	protected function getPackageName()
	{
		$class = get_class($this);

		$class = explode('\\', $class);

		$class = array_slice($class, -3); //we expect that namespace ends with PackageName/Controller

		return $class[0];
	}

	protected function checkActionPermission($foo, $bar)
	{
		//stub
	}
}
