<?php

namespace Supra\Validator\Type;

/**
 * 8 byte integer data type
 */
class BigIntType extends IntegerType
{
	const RANGE_MAX = '9223372036854775808';
	
	/**
	 * Overriden not to convert to integer value for 32bit systems
	 * @override
	 * @param string $value
	 * @return string
	 */
	protected function parseInt($value)
	{
		return $value;
	}
}
