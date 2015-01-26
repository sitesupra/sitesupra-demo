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

/**
 * @Entity
 * @Table(indexes={
 *		@index(name="name_idx", columns={"name"}),
 *		@index(name="localization_name_idx", columns={"localization_id", "name"})
 * })
 */
class LocalizationTag extends Abstraction\Entity
{
	/**
	 * @ManyToOne(targetEntity="Supra\Package\Cms\Entity\Abstraction\Localization", inversedBy="tags")
	 * @var Abstraction\Localization
	 */
	protected $localization;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $name;

	/**
	 * @param Abstraction\Localization $localization
	 */
	public function setLocalization(Abstraction\Localization $localization)
	{
		$this->localization = $localization;
	}
	
	/**
	 * @return Abstraction\Localization
	 */
	public function getLocalization()
	{
		return $this->localization;
	}
	
	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}
	
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
}
