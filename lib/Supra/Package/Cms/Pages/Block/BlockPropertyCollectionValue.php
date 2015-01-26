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

namespace Supra\Package\Cms\Pages\Block;

use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Pages\Block\Config\AbstractPropertyConfig;
use Supra\Package\Cms\Pages\Block\Config\PropertyListConfig;
use Supra\Package\Cms\Pages\Block\Config\PropertySetConfig;
use Supra\Package\Cms\Pages\BlockController;
use Supra\Package\Cms\Pages\Block\Config\PropertyCollectionConfig;

class BlockPropertyCollectionValue implements \ArrayAccess, \Countable, \IteratorAggregate
{
	/**
	 * @var BlockProperty
	 */
	private $collectionProperty;

	/**
	 * @var PropertyCollectionConfig
	 */
	private $collectionPropertyConfig;

	/**
	 * @var BlockController
	 */
	private $controller;

	/**
	 * @var array
	 */
	private $options;

	/**
	 * @var array
	 */
	private $values;

	/**
	 * @param BlockProperty $collectionProperty
	 * @param PropertyCollectionConfig $collectionPropertyConfig
	 * @param BlockController $controller
	 * @param array $options
	 */
	public function __construct(
			BlockProperty $collectionProperty,
			PropertyCollectionConfig $collectionPropertyConfig,
			BlockController $controller,
			array $options
	) {
		$this->collectionProperty = $collectionProperty;
		$this->collectionPropertyConfig = $collectionPropertyConfig;
		$this->controller = $controller;
		$this->options = $options;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getPropertyValue($name)
	{
		if ($this->collectionPropertyConfig instanceof PropertyListConfig
				&& ! $this->offsetExists($name)) {

			throw new \RuntimeException("Property [{$name}] is missing.");
		}

		return $this->controller->getPropertyViewValue(
				$this->collectionProperty->getHierarchicalName() . '.' . $name
		);
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function offsetExists($offset)
	{
		foreach ($this->collectionProperty->getProperties() as $property) {
			if ($property->getName() == $offset) {
				return true;
			}
		}

		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetGet($offset)
	{
		return $this->getPropertyValue($offset);
	}

	/**
	 * {@inheritDoc}
	 * @throws \BadMethodCallException
	 */
	public function offsetSet($offset, $value)
	{
		throw new \BadMethodCallException('Collection is read only.');
	}

	/**
	 * {@inheritDoc}
	 * @throws \BadMethodCallException
	 */
	public function offsetUnset($offset)
	{
		throw new \BadMethodCallException('Collection is read only.');
	}

	/**
	 * {@inheritDoc}
	 */
	public function count()
	{
		return $this->collectionProperty
				->getProperties()
				->count();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->getAllValues());
	}

	/**
	 * @return array
	 */
	private function getAllValues()
	{
		if ($this->values === null) {

			$this->values = array();

			if ($this->collectionPropertyConfig instanceof PropertySetConfig) {

				foreach ($this->collectionPropertyConfig as $config) {
					/* @var $config AbstractPropertyConfig */
					$this->values[$config->name] = $this->controller->getPropertyViewValue(
						$config->getHierarchicalName(),
						$this->options
					);
				}
			} else {
				foreach ($this->collectionProperty as $property) {
					/* @var $property BlockProperty */

					$value = $this->controller->getPropertyViewValue(
						$property->getHierarchicalName(),
						$this->options
					);

					if ($value !== null) {
						$this->values[$property->getName()] = $value;
					}
				}
			}
		}

		return $this->values;
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call($name, $arguments)
	{
		return $this->getPropertyValue($name);
	}
}