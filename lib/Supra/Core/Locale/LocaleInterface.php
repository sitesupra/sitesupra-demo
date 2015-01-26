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

namespace Supra\Core\Locale;

/**
 * Locale object
 */
interface LocaleInterface
{

	/**
	 * @return string
	 * @throws Exception\RuntimeException
	 */
	public function getId();

	/**
	 * @param string $id
	 */
	public function setId($id);

	/**
	 * @return string
	 */
	public function getTitle();

	/**
	 * @param string $title
	 */
	public function setTitle($title);

	/**
	 * @return string
	 */
	public function getCountry();

	/**
	 * @param string $country
	 */
	public function setCountry($country);

	/**
	 * @return array
	 */
	public function getProperties();

	/**
	 * @param array $properties
	 */
	public function setProperties($properties);

	/**
	 * Sets property
	 * @param string $name
	 * @param mixed $value 
	 */
	public function addProperty($name, $value);

	/**
	 * Returns propery
	 * @param string $name
	 * @return mixed
	 */
	public function getProperty($name);

	/**
	 * 
	 * @return boolean
	 */
	public function isActive();

	/**
	 * 
	 * @param boolean $active
	 */
	public function setActive($active);

	/**
	 * @return boolean
	 */
	public function isDefault();

	/**
	 * @param boolean $default
	 */
	public function setDefault($default);

	/**
	 * @return string
	 */
	public function getContext();
}
