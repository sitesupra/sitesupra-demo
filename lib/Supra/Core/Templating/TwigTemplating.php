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

namespace Supra\Core\Templating;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Templating\Loader\TemplateLoader;

class TwigTemplating implements Templating, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var \Twig_Environment
	 */
	protected $twig;

	/**
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
		$this->twig->getLoader()->setContainer($container);
		$this->twig->setCache($container->getParameter('directories.cache') . DIRECTORY_SEPARATOR . 'twig');
	}

	public function __construct()
	{
		//@todo: this is hardcode. we should move it to some templating component
		$loader = new TemplateLoader();

		$this->twig = new \Twig_Environment($loader);
		$this->twig->enableStrictVariables();

	}

	public function render($template, $parameters)
	{
		//@todo: this also should be refactored to some more generic way in case if we have some multiple templating engines active
		return $this->twig->render($template, $parameters);
	}

	public function addGlobal($name, $value)
	{
		//@todo: this also should be refactored to some more generic way in case if we have some multiple templating engines active
		$this->twig->addGlobal($name, $value);
	}

	public function addExtension($extension)
	{
		//@todo: this also should be refactored to some more generic way in case if we have some multiple templating engines active
		$this->twig->addExtension($extension);
	}

	public function getExtension($name)
	{
		return $this->twig->getExtension($name);
	}

	/**
	 * @return \Twig_Environment
	 */
	public function getTwig()
	{
		return $this->twig;
	}
}
