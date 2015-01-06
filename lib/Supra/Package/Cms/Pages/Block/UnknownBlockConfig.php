<?php

namespace Supra\Package\Cms\Pages\Block;

class UnknownBlockConfig extends Config\BlockConfig
{
	protected function configureAttributes(Mapper\AttributeMapper $mapper)
	{
		$mapper->title('Unknown block')
				->hidden();
	}

	protected function configureProperties(Mapper\PropertyMapper $mapper)
	{
		$mapper->disableAutoDiscovering();
	}

	public function getControllerClass()
	{
		return UnknownBlockController::className;
	}
}