<?php

namespace Supra\Controller\Layout\Theme\Configuration\Parameter;

use Supra\Controller\Layout\Theme\Configuration\ThemeParameterConfigurationAbstraction;
use Supra\Controller\Pages\Entity\Theme\Parameter\FontParameter;

class FontParameterConfiguration extends ThemeParameterConfigurationAbstraction
{
	/**
	 * @param array $outputValue
	 */
	public function makeOutputValue(&$outputValue)
	{
		foreach ($this->fonts as $fontData) {

			if ($fontData['title'] == $outputValue) {
				$outputValue = $fontData;
				break;
			}
		}
	}

	protected function getParameterClass()
	{
		return FontParameter::CN();
	}

}