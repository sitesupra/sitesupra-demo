<?php

namespace Supra\Package\Cms\Pages\Block\Mapper;

use Supra\Package\Cms\Editable\Editable;
use Supra\Package\Cms\Pages\Block\Config;

class PropertyMapper extends Mapper
{
	/**
	 * Shortcut for BlockConfiguration::addProperty()
	 *
	 * @param string $name
	 * @param string $editableName
	 * @param array $editableOptions
	 * @return \Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper
	 */
	public function add($name, $editableName, array $editableOptions = array())
	{
		$this->configuration->addProperty(
				$this->createProperty($name, $editableName, $editableOptions)
		);
		
		return $this;
	}

	/**
	 * @param string $name
	 * @param array $items
	 * @return \Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper
	 */
	public function addSet($name, array $items)
	{
		$this->configuration->addProperty(
				$this->createPropertySet($name, $items)
		);

		return $this;
	}

	/**
	 * @param string $name
	 * @param Config\AbstractProperty
	 * @return \Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper
	 */
	public function addCollection($name, Config\AbstractProperty $item)
	{
		$this->configuration->addProperty(
				$this->createPropertyCollection($name, $item)
		);

		return $this;
	}

	/**
	 * Shortcut for BlockConfiguration::setAutoDiscoverProperties()
	 *
	 * @param bool $autoDiscover
	 * @return \Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper
	 */
	public function autoDiscover($autoDiscover = true)
	{
		$this->configuration->setAutoDiscoverProperties($autoDiscover);

		return $this;
	}

	/**
	 * @param string $name
	 * @param string $editableName
	 * @param array $editableOptions
	 * @return Config\Property
	 */
	public function createProperty($name, $editableName, array $editableOptions = array())
	{
		return new Config\Property($name, $this->createEditable($editableName, $editableOptions));
	}

	/**
	 * @param string $name
	 * @param array $setItems
	 * @return Config\PropertySet
	 */
	public function createPropertySet($name, array $setItems)
	{
		$set = new Config\PropertySet($name);

		foreach ($setItems as $item) {
			$set->addSetItem($item);
		}

		return $set;
	}

	/**
	 * @param string $name
	 * @param Config\AbstractProperty $collectionItem
	 * @return Config\PropertyCollection
	 */
	public function createPropertyCollection($name, Config\AbstractProperty $collectionItem)
	{
		$collection = new Config\PropertyCollection($name);

		$collection->setCollectionItem($collectionItem);

		return $collection;
	}

	/**
	 * @param string $name
	 * @param array $options
	 * @return Editable
	 */
	private function createEditable($name, array $options)
	{
		$editable = Editable::getEditable($name);
		$editable->setOptions($options);

		return $editable;
	}
}
