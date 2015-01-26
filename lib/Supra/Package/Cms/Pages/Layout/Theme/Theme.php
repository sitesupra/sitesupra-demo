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

/**
 * Abstract theme implementation.
 */
abstract class Theme implements ThemeInterface
{
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var ThemeLayoutInterface[]
	 */
	protected $layouts = array();

	/**
	 * @return ThemeLayoutInterface[]
	 */
	public function getLayouts()
	{
		return $this->layouts;
	}

	/**
	 * @param string $name
	 * @param string $title
	 * @param string $fileName
	 */
	public function addLayout($name, $title, $fileName)
	{
		if ($this->hasLayout($name)) {
			throw new \RuntimeException(sprintf(
				'Theme [%s] already has layout [%s]',
				$this->getName(),
				$name
			));
		}

		$this->layouts[$name] = new Layout($name, $title, $fileName);
	}

	/**
	 * @param string $name
	 * @return ThemeLayoutInterface
	 */
	public function getLayout($name)
	{
		return $this->hasLayout($name) ? $this->layouts[$name] : null;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasLayout($name)
	{
		return isset($this->layouts[$name]);
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		if (empty($this->name)) {
			throw new \LogicException('Theme name is not set.');
		}

		return $this->name;
	}
}