<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfigurationAbstraction;
use Supra\Controller\Pages\Entity\Theme\Parameter\ColorParameter;

class ColorParameterConfiguration extends ThemeParameterConfigurationAbstraction
{

	protected function getParameterClass()
	{
		return ColorParameter::CN();
	}

}
