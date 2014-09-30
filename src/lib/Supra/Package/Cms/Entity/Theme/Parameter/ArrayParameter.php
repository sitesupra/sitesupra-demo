<?php

namespace Supra\Package\Cms\Entity\Theme\Parameter;

use Supra\Controller\Pages\Entity\Theme\ThemeParameterValue;

/**
 * @Entity
 */
class ArrayParameter extends ThemeParameterAbstraction
{

	/**
	 * @param ThemeParameterValue $parameterValue
	 * @return mixed
	 */
	public function getOuptutValueFromParameterValue(ThemeParameterValue $parameterValue)
	{
		$outputValue = unserialize($parameterValue->getValue());

		return $outputValue;
	}

	/**
	 * @param ThemeParameterValue $parameterValue
	 * @param mixed $input
	 */
	public function updateParameterValue(ThemeParameterValue $parameterValue, $input)
	{
		$parameterValue->setValue(serialize($input));
	}

}
