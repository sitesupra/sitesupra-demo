<?php

namespace Supra\Controller\Pages\Entity\Theme\Parameter;

use Supra\Controller\Pages\Entity\Theme\ThemeParameterValue;

/**
 * @Entity
 */
class CheckboxParameter extends ThemeParameterAbstraction
{
	/**
	 * @param ThemeParameterValue $parameterValue
	 * @return mixed
	 */
	public function getOuptutValueFromParameterValue(ThemeParameterValue $parameterValue)
	{
		$outputValue = $parameterValue->getValue();

		return $outputValue;
	}

	/**
	 * @param ThemeParameterValue $parameterValue
	 * @param mixed $input
	 */
	public function updateParameterValue(ThemeParameterValue $parameterValue, $input)
	{
		$boolType = new \Supra\Validator\Type\BooleanType();
		$boolType->validate($input);
		
		$parameterValue->setValue($input);
	}
}
