<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfigurationAbstraction;
use Supra\Controller\Pages\Entity\Theme\Parameter\MenuParameter;

class MenuParameterConfiguration extends ThemeParameterConfigurationAbstraction
{
	
	protected function getParameterClass()
	{
		return MenuParameter::CN();
	}
	
	public function getEditorType()
	{
		return 'Menu';
	}

}
