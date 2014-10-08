<?php

namespace Supra\Package\Cms\Pages\Block\Mapper;

use Supra\Package\Cms\Pages\Block\BlockPropertyConfiguration;
use Supra\Package\Cms\Editable\EditableAbstraction;

class PropertyMapper extends Mapper
{
	public function add($name, $editable, $label = null, $defaultValue = null, array $options = array())
	{
		$editableInstance = EditableAbstraction::get($editable);

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

