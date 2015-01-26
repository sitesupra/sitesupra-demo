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

namespace Supra\Core\Doctrine;

use Doctrine\Common\Persistence\AbstractManagerRegistry;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;

class ManagerRegistry extends AbstractManagerRegistry implements ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @param \Supra\Core\DependencyInjection\ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * Fetches/creates the given services
	 *
	 * A service in this context is connection or a manager instance
	 *
	 * @param string $name name of the service
	 * @return object instance of the given service
	 */
	protected function getService($name)
	{
		if (!isset($this->container[$name])) {
			throw new \Exception(sprintf('There is no service (connection or entity manager) with id "%s"', $name));
		}

		return $this->container[$name];
	}

	/**
	 * Resets the given services
	 *
	 * A service in this context is connection or a manager instance
	 *
	 * @param string $name name of the service
	 * @return void
	 */
	protected function resetService($name)
	{
		throw new \Exception(__NAMESPACE__.__METHOD__.' is not yet implemented');
	}

	/**
	 * Resolves a registered namespace alias to the full namespace.
	 *
	 * This method looks for the alias in all registered object managers.
	 *
	 * @param string $alias The alias
	 *
	 * @return string The full namespace
	 */
	function getAliasNamespace($alias)
	{
		throw new \Exception(__NAMESPACE__.__METHOD__.' is not yet implemented');
	}

}