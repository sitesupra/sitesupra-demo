<?php

namespace Supra\Controller\Pages\Entity\Theme\Parameter;

/**
 * @Entity
 */
class ImageParameter extends ThemeParameterAbstraction
{

	/**
	 * @param ThemeParameterValue $parameterValue
	 * @return mixed
	 */
	public function getOuptutValueFromParameterValue(ThemeParameterValue $parameterValue)
	{
		$value = $parameterValue->getValue();
		
		$outputValue = null;
		
		return $outputValue;
	}

}
