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

namespace Supra\Package\Cms\Entity;

use Supra\Package\Cms\Uri\Path;

/**
 * Stores page full path
 * 
 * @Entity
 * @Table(uniqueConstraints={
 *		@UniqueConstraint(name="locale_path_idx", columns={"locale", "path"})
 * })
 */
class PageLocalizationPath extends Abstraction\Entity
{

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $locale;

	/**
	 * Limitation because of MySQL unique constraint 1k byte limit
	 * @Column(type="path", length=255, nullable=true)
	 * @var Path
	 */
	protected $path = null;

	/**
	 * Marks if the page is active
	 * @Column(type="boolean", nullable=false)
	 * @var boolean
	 */
	protected $active = true;

//	/**
//	 * Marks, if page is with limited access (requires an authorization)
//	 * @Column(type="boolean", nullable=false)
//	 * @var boolean
//	 */
//	protected $limited = false;

	/**
	 * Marks, if page is visible in sitemap
	 * @Column(type="boolean", nullable=false)
	 * @var boolean
	 */
	protected $visibleInSitemap = true;

	/**
	 * Path entity and owner localization ids are equals
	 * @param PageLocalization $localization
	 */
	public function __construct(PageLocalization $localization)
	{
		$this->id = $localization->getId();
		$this->locale = $localization->getLocaleId();
	}

	/**
	 * Special ID setter for path regeneration command so the ID in draft and 
	 * public schemes are equal
	 * @param string $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	public function getLocale()
	{
		return $this->locale;
	}

	public function setLocale($locale)
	{
		$this->locale = $locale;
	}

	/**
	 * @return Path
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @param mixed $path 
	 */
	public function setPath($path = null)
	{
		if ( ! $path instanceof Path && ! is_null($path)) {
			$path = new Path($path);
		}
		$this->path = $path;
	}

	/**
	 * @return boolean
	 */
	public function isActive()
	{
		return $this->active;
	}

	/**
	 * @param boolean $active
	 */
	public function setActive($active)
	{
		$this->active = $active;
	}

//	/**
//	 * @return boolean
//	 */
//	public function isLimited()
//	{
//		return $this->limited;
//	}
//
//	/**
//	 * @param boolean $limited
//	 */
//	public function setLimited($limited)
//	{
//		$this->limited = $limited;
//	}

	/**
	 * @return boolean
	 */
	public function isVisibleInSitemap()
	{
		return $this->visibleInSitemap;
	}

	/**
	 * @param boolean $visibleInSitemap 
	 */
	public function setVisibleInSitemap($visibleInSitemap)
	{
		$this->visibleInSitemap = $visibleInSitemap;
	}
	
}
