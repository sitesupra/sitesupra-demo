<?php

namespace Supra\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 */
class SubmitLabelToBooleanTransformer implements DataTransformerInterface
{
	/**
	 * Return true if received, false otherwise
	 * @param string $value
	 * @return boolean
	 */
	public function reverseTransform($value)
	{
		if (is_null($value)) {
			return false;
		}

		return true;
		
	}

	/**
	 * Value is not important
	 * @param string $value
	 * @return null|string
	 */
	public function transform($value)
	{
		return $value;
	}

}
