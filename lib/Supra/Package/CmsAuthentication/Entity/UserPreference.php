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

namespace Supra\Package\CmsAuthentication\Entity;

use Supra\Package\Cms\Entity\Abstraction\Entity;

/**
 * Single user preference item
 * @Entity
 */
class UserPreference extends Entity
{
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @Column(type="array", nullable=true)
	 * @var string
	 */
	protected $value;

	/**
	 * @ManyToOne(targetEntity="Supra\Package\CmsAuthentication\Entity\UserPreferencesCollection", inversedBy="preferences")
	 * @var UserPreferencesCollection
	 */
	protected $collection;

	/**
	 * @param string $name
	 * @param mixed $value
	 * @param User $user
	 */
	public function __construct($name, $value, UserPreferencesCollection $collection)
	{
		parent::__construct();

		$this->name = $name;
		$this->value = $value;
		$this->collection = $collection;
	}

	/**
	 * @return UserPreferencesCollection
	 */
	public function getCollection()
	{
		return $this->collection;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param mixed $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * @param User $user
	 */
	public function setCollection(UserPreferencesCollection $collection)
	{
		$this->collection = $collection;
	}

}
