<?php

namespace Supra\Package\Cms\Pages\Block\Mapper;

use Supra\Package\Cms\Pages\Block\BlockPropertyConfiguration;
use Supra\Package\Cms\Editable\Editable;

class PropertyMapper extends Mapper
{
	/**
	 * Shortcut for BlockConfiguration::addProperty()
	 *
	 * @param string $name
	 * @param string $editableName
	 * @param string $label
	 * @param string $defaultValue
	 * @param array $options
	 * @return \Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper
	 */
	public function add($name, $editableName, $label = null, $defaultValue = null, array $options = array())
	{
		$editableInstance = Editable::getEditable($editableName);

		$editableInstance->setLabel($label);
		$editableInstance->setDefaultValue($defaultValue);

		$this->configuration->addProperty(
				new BlockPropertyConfiguration(
						$name,
						$editableInstance,
						$defaultValue,
						$options
				)
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
}
