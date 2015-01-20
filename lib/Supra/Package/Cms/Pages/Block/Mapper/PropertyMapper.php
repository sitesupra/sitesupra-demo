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
	 * @param string $editableType
	 * @param array $editableOptions
	 * @return \Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper
	 */
	public function add($name, $editableType, array $editableOptions = array())
	{
		$this->config->addProperty(
				$this->createProperty($name, $editableType, $editableOptions)
		);
		
		return $this;
	}

	/**
	 * @param Config\AbstractPropertyConfig $config
	 * @return \Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper
	 */
	public function addProperty(Config\AbstractPropertyConfig $config)
	{
		$this->config->addProperty($config);
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @param array $items
	 * @return \Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper
	 */
	public function addSet($name, $label, array $items)
	{
		$this->config->addProperty(
				$this->createPropertySet($name, $label, $items)
		);

		return $this;
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @param Config\AbstractPropertyConfig $item
	 * @return \Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper
	 */
	public function addList($name, $label, Config\AbstractPropertyConfig $item)
	{
		$this->config->addProperty(
				$this->createPropertyList($name, $label, $item)
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
	 * Shortcut for BlockConfig::setAutoDiscoverProperties(false).
	 *
	 * @return \Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper
	 */
	public function disableAutoDiscovering()
	{
		$this->config->setAutoDiscoverProperties(false);
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $editableType
	 * @param array $editableOptions
	 * @return Config\PropertyConfig
	 */
	public function createProperty($name, $editableType, array $editableOptions = array())
	{
		return new Config\PropertyConfig($name, $this->createEditable($editableType, $editableOptions));
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @param array $setItems
	 * @return Config\PropertySetConfig
	 */
	public function createPropertySet($name, $label, array $setItems)
	{
		$set = new Config\PropertySetConfig($name);

		foreach ($setItems as $item) {
			$set->addSetItem($item);
		}

		$set->setLabel($label);

		return $set;
	}

	/**
	 * @param string $name
	 * @param string $label
	 * @param Config\AbstractPropertyConfig $listItem
	 * @return Config\PropertyListConfig
	 */
	public function createPropertyList($name, $label, Config\AbstractPropertyConfig $listItem)
	{
		$list = new Config\PropertyListConfig($name);

		$list->setLabel($label);

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
