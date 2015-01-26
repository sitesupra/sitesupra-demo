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

namespace Supra\Core\Package;

use Doctrine\Common\Util\Inflector;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;

abstract class AbstractSupraPackage implements SupraPackageInterface, ContainerAware
{
	/**
	 * Dependency injection container
	 *
	 * @var \Supra\Core\DependencyInjection\ContainerInterface
	 */
	protected $container;

	/**
	 * Underscore name of a package
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * @var ConfigurationInterface
	 */
	protected $configuration;

	public function __construct()
	{
		$this->name = $this->getName();
	}

	/**
	 * Creates a name for a command that can be used throughout configuration files
	 *
	 * @return string
	 */
	public function getName()
	{
		$class = get_class($this);

		$class = explode('\\', $class);

		$class = $class[count($class) - 1];

		$class = str_replace(array('Supra', 'Package'), '', $class);

		$inflector = new Inflector();
		$name = $inflector->tableize($class);

		return $name;
	}

	public function getConfiguration()
	{
		if ($this->configuration) {
			return $this->configuration;
		}

		$class = explode('\\', get_class($this));

		$className = array_pop($class);

		array_push($class, 'Configuration');
		array_push($class, $className.'Configuration');

		$class = '\\'.implode('\\', $class);

		return $this->configuration = new $class();
	}

	public function loadConfiguration(ContainerInterface $container, $file = 'config.yml')
	{
		$file = $container->getApplication()->locateConfigFile($this, $file);

		$data = $container['config.universal_loader']->load($file);

		return $container->getApplication()->addConfigurationSection($this, $data);
	}

	public function boot()
	{
	}

	public function inject(ContainerInterface $container)
	{
	}

	public function finish(ContainerInterface $container)
	{
	}

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function shutdown()
	{
	}

}
