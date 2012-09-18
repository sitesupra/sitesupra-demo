<?php

namespace Supra\Controller\Pages\Entity\Theme\Parameter;

use Supra\Controller\Pages\Entity\Theme\ThemeParameterValue;

/**
 * @Entity
 */
class FontParameter extends ThemeParameterAbstraction
{

	/**
	 * @param ThemeParameterValue $parameterValue
	 * @return mixed
	 */
	public function getOuptutValueFromParameterValue(ThemeParameterValue $parameterValue)
	{
		$configuration = $this->getConfiguration();

		$value = $parameterValue->getValue();

		$outputValue = null;

		foreach ($configuration->values as $fontData) {
			if ($fontData['id'] == $value) {
				$outputValue = $fontData;
				break;
			}
		}

		return $outputValue;
	}

	/**
	 * @param ThemeParameterValue $parameterValue
	 * @return mixed
	 */
	public function getLessOuptutValueFromParameterValue(ThemeParameterValue $parameterValue)
	{
		return $this->getOuptutValueFromParameterValue($parameterValue);
	}

}