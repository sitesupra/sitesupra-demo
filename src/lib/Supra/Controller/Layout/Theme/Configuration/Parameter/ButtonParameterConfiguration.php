<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfigurationAbstraction;
use Supra\Controller\Pages\Entity\Theme\Parameter\ButtonParameter;

class ButtonParameterConfiguration extends ThemeParameterConfigurationAbstraction
{

	protected function getParameterClass()
	{
		return ButtonParameter::CN();
	}
	
	public function getEditorType()
	{
		return 'Button';
	}

}
