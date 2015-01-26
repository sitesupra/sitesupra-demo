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

namespace Supra\Package\Cms\Pages\Layout\Theme;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;

class DefaultThemeProvider implements ThemeProviderInterface, ContainerAware
{
	/**
	 * @var ThemeInterface[]
	 */
	protected $themes = array();

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @param ThemeInterface $theme
	 * @throws \RuntimeException
	 */
	public function registerTheme(ThemeInterface $theme)
	{
		$themeName = $theme->getName();

		if (isset($this->themes[$themeName])) {
			throw new \RuntimeException(
					"Theme [{$themeName}] is already in collection."
			);
		}

		$this->themes[$themeName] = $theme;
	}

	/**
	 * @return ThemeInterface
	 */
	public function getActiveTheme()
	{
		if (! $this->container->hasParameter('cms.active_theme')) {
			throw new \RuntimeException('No active theme set.');
		}

		$themeName = $this->container->getParameter('cms.active_theme');

		if (! isset($this->themes[$themeName])) {
			throw new \InvalidArgumentException("There is no theme [$themeName].");
		}

		return $this->themes[$themeName];
	}

	/**
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}
}
