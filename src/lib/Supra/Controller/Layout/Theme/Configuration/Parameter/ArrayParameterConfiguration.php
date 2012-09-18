<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfigurationAbstraction;
use Supra\Controller\Pages\Entity\Theme\Parameter\ArrayParameter;

class ArrayParameterConfiguration extends ThemeParameterConfigurationAbstraction
{

	protected function getParameterClass()
	{
		return ArrayParameter::CN();
	}

}
