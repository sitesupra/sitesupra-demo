<?php

namespace Supra\Package\Cms\Pages\Block;

class UnknownBlockConfiguration extends BlockConfiguration
{
	protected function configureBlock(Mapper\BlockMapper $mapper)
	{
		$mapper->title('Unknown block')
				->hidden();
	}

	public function getControllerClass()
	{
		return UnknownBlockController::className;
	}
}