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

namespace Supra\Package\Framework\Twig;

use Supra\Core\Application\ApplicationManager;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Locale\LocaleManager;

class SupraGlobal implements ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * Array of string to include in template
	 *
	 * @var array
	 */
	protected $javascripts;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * @return ApplicationManager
	 */
	public function getApplication()
	{
		return $this->container->getApplicationManager()->getCurrentApplication();
	}

	public function getUser()
	{
		$context = $this->container->getSecurityContext();

		$token = $context->getToken();

		if (!$token) {
			return false;
		}

		$user = $token->getUser();

		return $user;
	}

	/**
	 * @return LocaleManager
	 */
	public function getLocaleManager()
	{
		return $this->container->getLocaleManager();
	}

	public function renderJavascripts()
	{
		if (count($this->javascripts)) {
			throw new \Exception('Rendering additional javascripts is not yet implemented');
		}
	}
}