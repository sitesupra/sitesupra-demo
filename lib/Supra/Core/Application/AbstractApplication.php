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

namespace Supra\Core\Application;

class AbstractApplication implements ApplicationInterface
{
	const APPLICATION_ACCESS_PUBLIC = 'public';
	const APPLICATION_ACCESS_PRIVATE = 'private';
	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $url;

	/**
	 * @var string
	 */
	protected $icon;

	/**
	 * @var string
	 */
	protected $route;

	/**
	 * @var string
	 */
	protected $access = self::APPLICATION_ACCESS_PRIVATE;

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @return string
	 */
	public function getIcon()
	{
		return $this->icon;
	}

	/**
	 * @param string $route
	 */
	public function setRoute($route)
	{
		$this->route = $route;
	}

	/**
	 * @return string
	 */
	public function getRoute()
	{
		return $this->route;
	}

	/**
	 * @return bool
	 */
	public function isPublic()
	{
		return $this->access == $this::APPLICATION_ACCESS_PUBLIC;
	}

	/**
	 * @return bool
	 */
	public function isPrivate()
	{
		return $this->access == $this::APPLICATION_ACCESS_PRIVATE;
	}

}