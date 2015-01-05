<?php

namespace Supra\Package\Cms\Pages\Block\Mapper;

use Supra\Package\Cms\Editable\Editable;
use Supra\Package\Cms\Pages\Block\Config;

class PropertyMapper extends Mapper
{
	/**
	 * Shortcut for BlockConfig::addProperty()
	 *
	 * @param string $name
	 * @param string $editableName
	 * @param array $editableOptions
	 * @return \Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper
	 */
	public function add($name, $editableName, array $editableOptions = array())
	{
		$this->config->addProperty(
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
		$this->config->addProperty(
				$this->createPropertySet($name, $items)
		);

		return $this;
	}

	/**
	 * @param string $name
	 * @param Config\AbstractPropertyConfig $item
	 * @return \Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper
	 */
	public function addList($name, Config\AbstractPropertyConfig $item)
	{
		$this->config->addProperty(
				$this->createPropertyList($name, $item)
		);

		return $this;
	}

	/**
	 * Shortcut for BlockConfig::setAutoDiscoverProperties()
	 *
	 * @param bool $autoDiscover
	 * @return \Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper
	 */
	public function autoDiscover($autoDiscover = true)
	{
		$this->config->setAutoDiscoverProperties($autoDiscover);
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $editableName
	 * @param array $editableOptions
	 * @return Config\PropertyConfig
	 */
	public function createProperty($name, $editableName, array $editableOptions = array())
	{
		return new Config\PropertyConfig($name, $this->createEditable($editableName, $editableOptions));
	}

	/**
	 * @param string $name
	 * @param array $setItems
	 * @return Config\PropertySetConfig
	 */
	public function createPropertySet($name, array $setItems)
	{
		$set = new Config\PropertySetConfig($name);

		foreach ($setItems as $item) {
			$set->addSetItem($item);
		}

		return $set;
	}

	/**
	 * @param string $name
	 * @param Config\AbstractPropertyConfig $listItem
	 * @return Config\PropertyListConfig
	 */
	public function createPropertyList($name, Config\AbstractPropertyConfig $listItem)
	{
		$list = new Config\PropertyListConfig($name);

		$list->setListItem($listItem);

		return $list;
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
