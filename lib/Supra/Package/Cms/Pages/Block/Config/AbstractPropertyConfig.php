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

namespace Supra\Package\Cms\Pages\Block\Config;

use Supra\Package\Cms\Editable\Editable;
use Supra\Package\Cms\Entity\BlockProperty;

abstract class AbstractPropertyConfig
{
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var AbstractPropertyConfig
	 */
	protected $parent;

	/**
	 * @param string $name
	 */
	public function __construct($name)
	{
		if (strpos($name, '.') !== false) {
			throw new \InvalidArgumentException('Dots are not allowed.');
		}

		$this->name = $name;
	}

	/**
	 * @param AbstractPropertyConfig $parent
	 */
	public function setParent(AbstractPropertyConfig $parent)
	{
		$this->parent = $parent;
	}

	/**
	 * @return bool
	 */
	public function hasParent()
	{
		return $this->parent !== null;
	}

	/**
	 * @return AbstractPropertyConfig
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * @return string
	 */
	public function getHierarchicalName()
	{
		if ($this->parent === null) {
			return $this->name;
		}

		return $this->parent->getHierarchicalName() . '.' . $this->name;
	}

	/**
	 * @param BlockProperty $property
	 * @return bool
	 */
	abstract public function isMatchingProperty(BlockProperty $property);

	/**
	 * @param string $name
	 * @return BlockProperty
	 */
	abstract public function createProperty($name);

	/**
	 * @return Editable
	 */
	abstract public function getEditable();
}