<?php

namespace Supra\Package\Cms\Pages\Block\Mapper;

use Supra\Package\Cms\Pages\Block\BlockPropertyConfiguration;
use Supra\Package\Cms\Editable\Editable;

class PropertyMapper extends Mapper
{
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
//
//	public function addGroup($label, array $properties)
//	{
//	}
}

