<?php

namespace Supra\Validator\Type;

use Supra\Validator\Exception;

/**
 * Boolean type
 */
class BooleanType extends AbstractType
{
	private static $yes = array(
		'1', 'on', 'true', 'yes', 'y'
	);
	
	private static $no = array(
		'', '0', 'off', 'false', 'no', 'n', 'null', 'nil'
	);
	
	/**
	 * @param string $value
	 */
	public function validate(&$value)
	{
		$checkValue = strtolower( (string) $value);
		
		if (in_array($checkValue, self::$no, true)) {
			$value = false;
		} elseif (in_array($checkValue, self::$yes, true)) {
			$value = true;
		} else {
			throw new Exception\ValidationFailure("Boolean value $value not recognized");
		}
	}
}
