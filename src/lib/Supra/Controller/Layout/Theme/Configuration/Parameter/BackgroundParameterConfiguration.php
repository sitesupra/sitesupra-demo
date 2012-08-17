<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfigurationAbstraction;
use Supra\Controller\Pages\Entity\Theme\Parameter\BackgroundParameter;

class BackgroundParameterConfiguration extends ThemeParameterConfigurationAbstraction
{

	/**
	 * @return string
	 */
	protected function getParameterClass()
	{
		return BackgroundParameter::CN();
	}

}
