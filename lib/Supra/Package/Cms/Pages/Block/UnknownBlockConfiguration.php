<?php

namespace Supra\Package\Cms\Pages\Block;

class UnknownBlockConfiguration extends BlockConfiguration
{
	protected function configureAttributes(Mapper\AttributeMapper $mapper)
	{
		$mapper->title('Unknown block')
				->hidden();
	}

	public function getControllerClass()
	{
		return UnknownBlockController::className;
	}
}