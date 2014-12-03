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
	 * @param array $editableOptions
	 * @return \Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper
	 */
	public function add($name, $editableName, array $editableOptions = array())
	{
		$editable = Editable::getEditable($editableName);

		$editable->setOptions($editableOptions);

		$this->configuration->addProperty(new BlockPropertyConfiguration($name, $editable));

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
