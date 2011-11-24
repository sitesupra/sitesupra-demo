<?php

namespace Supra\Validator\Type;

use Supra\Validator\Exception;

/**
 * 4 byte integer data type
 */
class IntegerType extends AbstractType
{
	const RANGE_MAX = '2147483648';
	
	/**
	 * @param string $value
	 */
	public function validate(&$value)
	{
		$tokens = null;
		
		if ( ! preg_match('/^(\-|\+)?0*(\d+)$/', $value, $tokens)) {
			throw new Exception\ValidationFailure("Doesn't consist of digits");
		}
		
		$sign = ! empty($tokens[1]) ? $tokens[1] : '+';
		$absolute = $tokens[2];
		$max = static::RANGE_MAX;
		
		$maxLength = strlen($max);
		$length = strlen($absolute);
		$tooBig = false;
		
		if ($length > $maxLength) {
			$tooBig = true;
			
		} elseif ($length == $maxLength) {
			
			if (strcmp($absolute, $max) > 0) {
				$tooBig = true;
			}
			
			if ($sign == '+' && strcmp($absolute, $max) === 0) {
				$tooBig = true;
			}
		}
		
		if ($tooBig) {
			throw new Exception\ValidationFailure('Value exceeds integer limits');
		}
		
		$value = $this->parseInt($value);
	}
	
	/**
	 * Convert to integer
	 * @param string $value
	 * @return int
	 */
	protected function parseInt($value)
	{
		return (int) $value;
	}
}
