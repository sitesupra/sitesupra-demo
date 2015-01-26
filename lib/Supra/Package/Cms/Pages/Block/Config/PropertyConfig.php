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

class PropertyConfig extends AbstractPropertyConfig
{
	/**
	 * @var Editable
	 */
	protected $editable;

	/**
	 * @param string $name
	 * @param Editable $editable
	 */
	public function __construct($name, Editable $editable)
	{
		parent::__construct($name);
		$this->editable = $editable;
	}

	/**
	 * @return Editable
	 */
	public function getEditable()
	{
		return $this->editable;
	}

	/**
	 * {@inhertitDoc}
	 */
	public function isMatchingProperty(BlockProperty $property)
	{
		if ($this->parent instanceof PropertyListConfig) {
			return $property->getHierarchicalName() === $this->parent->getHierarchicalName() . '.' . $property->getName()
					&& $property->getEditableClass() === get_class($this->editable);
		}

		return $property->getHierarchicalName() === $this->getHierarchicalName()
				&& $property->getEditableClass() === get_class($this->editable);
	}

	/**
	 * {@inheritDoc}
	 */
	public function createProperty($name)
	{
		$property = new BlockProperty($name);
		$property->setEditableClass(get_class($this->editable));

		// @TODO: value conversion
		// @TODO: localized values
		$property->setValue($this->editable->getDefaultValue());

		return $property;
	}
}