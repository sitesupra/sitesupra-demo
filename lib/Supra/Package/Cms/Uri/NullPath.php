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

namespace Supra\Package\Cms\Uri;

/**
 * Null path with no path parts
 */
class NullPath extends Path
{
	/**
	 * @var NullPath
	 */
	private static $instance = null;

	/**
	 * @return NullPath
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	public function __toString()
	{
		return '';
	}

	public function append(Path $path = null)
	{
		return $this;
	}

	public function appendString($pathString)
	{
		return $this;
	}

	public function equals(Path $path = null)
	{
		return false;
	}

	public function getBasePath($offset = 0, $format = self::FORMAT_NO_DELIMITERS)
	{
		return null;
	}

	public function getDepth()
	{
		return null;
	}

	public function getFullPath($format = self::FORMAT_NO_DELIMITERS)
	{
		return null;
	}

	public function getPath($format = self::FORMAT_NO_DELIMITERS)
	{
		return null;
	}

	public function getPathList()
	{
		return array();
	}

	public function getSeparator()
	{
		return null;
	}

	public function isEmpty()
	{
		return true;
	}

	public function prepend(Path $path = null)
	{
		return $this;
	}

	public function prependString($pathString)
	{
		return $this;
	}

	public function startsWith(Path $path)
	{
		return false;
	}

}
