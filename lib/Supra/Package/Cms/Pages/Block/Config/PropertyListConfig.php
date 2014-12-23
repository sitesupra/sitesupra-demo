<?php

namespace Supra\Package\Cms\Pages\Block\Config;

use Supra\Package\Cms\Entity\BlockProperty;

class PropertyListConfig extends AbstractPropertyConfig implements PropertyCollectionConfig
{
	/**
	 * @var AbstractPropertyConfig
	 */
	protected $item;

	/**
	 * @param AbstractPropertyConfig $item
	 */
	public function setListItem(AbstractPropertyConfig $item)
	{
		$item->setParent($this);
		$this->item = $item;
	}

	/**
	 * @return AbstractPropertyConfig
	 */
	public function getListItem()
	{
		return $this->item;
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
}