<?php

namespace Supra\Validator\Type;

/**
 * 
 */
class IntegerType implements ValidationTypeInterface
{
	/**
	 * @param string $value 
	 */
	public function validate(&$value)
	{
		if ( ! ctype_digit($value)) {
			throw new \InvalidArgumentException("Doesn't consist of digits");
		}
	}
}
