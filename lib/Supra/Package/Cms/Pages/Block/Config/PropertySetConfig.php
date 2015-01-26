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

use Supra\Package\Cms\Entity\BlockProperty;

class PropertySetConfig extends AbstractPropertyConfig implements PropertyCollectionConfig, \IteratorAggregate
{
	/**
	 * @var AbstractPropertyConfig[]
	 */
	protected $items = array();

	/**
	 * @var string
	 */
	protected $label;

	/**
	 * @param string $label
	 */
	public function setLabel($label)
	{
		$this->label = $label;
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->label;
	}

	/**
	 * @param AbstractPropertyConfig $item
	 */
	public function addSetItem(AbstractPropertyConfig $item)
	{
		$item->setParent($this);
		$this->items[$item->name] = $item;
	}

	/**
	 * @param string $name
	 * @return AbstractPropertyConfig
	 * @throws \RuntimeException
	 */
	public function getSetItem($name)
	{
		if (! isset($this->items[$name])) {
			throw new \RuntimeException("Set [{$this->name}] has no definition for [{$name}].");
		}

		return $this->items[$name];
	}

	/**
	 * @return AbstractPropertyConfig[]
	 */
	public function getSetItems()
	{
		return $this->items;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isMatchingProperty(BlockProperty $property)
	{
		return $property->getHierarchicalName() == $this->name;
	}

	/**
	 * {@inheritDoc}
	 */
	public function createProperty($name)
	{
		return new BlockProperty($name);
	}

	/**
	 * {@inheritDoc}
	 * @throws \LogicException
	 */
	public function getEditable()
	{
		throw new \LogicException('Collections have no editables.');
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->items);
	}
}