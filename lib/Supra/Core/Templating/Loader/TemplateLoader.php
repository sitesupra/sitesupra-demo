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

namespace Supra\Core\Templating\Loader;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Package\PackageLocator;
use Twig_Error_Loader;

class TemplateLoader implements \Twig_LoaderInterface, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}


	/**
	 * Gets the source code of a template, given its name.
	 *
	 * @param string $name The name of the template to load
	 *
	 * @return string The template source code
	 *
	 * @throws Twig_Error_Loader When $name is not found
	 */
	public function getSource($name)
	{
		if (strpos($name, ':') === false) {
			throw new \Exception(
				sprintf('Oops, template file name does not seem to be in Package:file\name\path.html.twig format ("%s" given")', $name)
			);
		}

		list($packageName, $templatePath) = explode(':', $name);

		$path = $this->container->getApplication()->locateViewFile($packageName, $templatePath);

		return file_get_contents($path);
	}

	/**
	 * Gets the cache key to use for the cache for a given template name.
	 *
	 * @param string $name The name of the template to load
	 *
	 * @return string The cache key
	 *
	 * @throws Twig_Error_Loader When $name is not found
	 */
	public function getCacheKey($name)
	{
		return 'supra_'.md5($name);
	}

	/**
	 * Returns true if the template is still fresh.
	 *
	 * @param string $name The template name
	 * @param timestamp $time The last modification time of the cached template
	 *
	 * @return bool    true if the template is fresh, false otherwise
	 *
	 * @throws Twig_Error_Loader When $name is not found
	 */
	public function isFresh($name, $time)
	{
		throw new \Exception('fresh');
	}

}