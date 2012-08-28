<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfigurationAbstraction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\Theme\Parameter\ImageParameter;

class ImageParameterConfiguration extends ThemeParameterConfigurationAbstraction
{

	protected function getParameterClass()
	{
		return ImageParameter::CN();
	}

}
