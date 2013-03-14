<?php

namespace Supra\Controller\Pages\Entity\Theme\Parameter;

use Supra\Controller\Pages\Entity\Theme\ThemeParameterValue;

/**
 * @Entity
 */
class BackgroundParameter extends ThemeParameterAbstraction
{

	/**
	 * @param ThemeParameterValue $parameterValue
	 * @return mixed
	 */
	public function getOuptutValueFromParameterValue(ThemeParameterValue $parameterValue)
	{
		$configuration = $this->getConfiguration();

		$value = $parameterValue->getValue();
		
		if (empty($value)) {
			return null;
		}

		$outputValue = null;

		foreach ($configuration->values as $backgroundData) {

			if ($backgroundData['id'] === $value) {

				$backgroundData['icon'] = str_replace('//', '/', "'" . $backgroundData['icon'] . "'");

				$outputValue = $backgroundData;
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
