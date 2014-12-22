<?php

namespace Supra\Package\Cms\Pages\Block\Config;

use Supra\Package\Cms\Entity\BlockPropertyCollection;
use Supra\Package\Cms\Entity\BlockProperty;

class PropertySet extends AbstractProperty implements \IteratorAggregate
{
	/**
	 * @var AbstractProperty[]
	 */
	protected $items = array();

	/**
	 * @param AbstractProperty $item
	 */
	public function addSetItem(AbstractProperty $item)
	{
		$item->setParent($this);
		$this->items[$item->name] = $item;
	}

	/**
	 * @param string $name
	 * @return AbstractProperty
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
	 * @return AbstractProperty[]
	 */
	public function getSetItems()
	{
		return $this->items;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->items);
	}

	/**
	 * {@inheritDoc}
	 */
	public function createBlockProperty($name)
	{
		return new BlockPropertyCollection($name);
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function isMatchingProperty(BlockProperty $property)
	{
		return $property->getName() === $this->name
				&& $property instanceof BlockPropertyCollection;
	}
}