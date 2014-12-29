<?php

namespace Supra\Package\Cms\Pages\Block\Config;

use Supra\Package\Cms\Entity\BlockProperty;

class PropertySetConfig extends AbstractPropertyConfig implements PropertyCollectionConfig, \IteratorAggregate
{
	/**
	 * @var AbstractPropertyConfig[]
	 */
	protected $items = array();

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
		return $property->getName() === $this->name;
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