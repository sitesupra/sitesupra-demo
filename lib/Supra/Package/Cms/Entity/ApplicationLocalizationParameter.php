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
 * Application localization parameter
 * @Entity
 * @Table(uniqueConstraints={@UniqueConstraint(name="name_unique_idx", columns={"name", "localization_id"})}))
 */
class ApplicationLocalizationParameter extends Abstraction\Entity
{
//	/**
//	 * @ManyToOne(targetEntity="Supra\Package\Cms\Entity\ApplicationLocalization", inversedBy="parameters", cascade={"persist"})
//	 * @var \Supra\Controller\Pages\Entity\ApplicationLocalization
//	 */
//	protected $localization;

	/**
	 * @Column(type="supraId20", name="localization_id")
	 * @var string
	 */
	protected $localizationId;
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;
	
	/**
	 * @Column(type="text", nullable=true)
	 * @var string
	 */
	protected $value;
	
	/**
	 * @param string $name
	 */
	public function __construct($name)
	{
		parent::__construct();
		$this->name = $name;
	}
	
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}
	
	/**
	 * @return string|null
	 */
	public function getValue()
	{
		return $this->value;
	}
	
	/**
	 * @param string $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * @param ApplicationLocalization $localization
	 */
	public function setApplicationLocalization(ApplicationLocalization $localization)
	{
//		$this->localization = $localization;
//		$localization->addParameterToCollection($this);

		$this->localizationId = $localization->getId();
	}

//	/**
//	 * @return \Supra\Controller\Pages\Entity\ApplicationLocalization
//	 */
//	public function getApplicationLocalization()
//	{
//		return $this->localization;
//	}

	/**
	 * @return string
	 */
	public function getApplicationLocalizationId()
	{
		return $this->localizationId;
	}
}
