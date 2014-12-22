<?php

namespace Supra\Package\Cms\Pages\Block\Config;

use Supra\Package\Cms\Entity\BlockPropertyCollection;
use Supra\Package\Cms\Entity\BlockProperty;

class PropertyCollection extends AbstractProperty
{
	/**
	 * @var AbstractProperty
	 */
	protected $item;

	/**
	 * @param AbstractProperty $item
	 */
	public function setCollectionItem(AbstractProperty $item)
	{
		$item->setParent($this);
		$this->item = $item;
	}

	/**
	 * @return AbstractProperty
	 */
	public function getCollectionItem()
	{
		return $this->item;
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