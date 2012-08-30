<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfigurationAbstraction;
use Supra\Controller\Pages\Entity\Theme\Parameter\FontParameter;

class FontParameterConfiguration extends ThemeParameterConfigurationAbstraction
{

	protected function getParameterClass()
	{
		return FontParameter::CN();
	}

}