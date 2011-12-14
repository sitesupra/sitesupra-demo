<?php

namespace Supra\Validator\Type;

use Supra\Validator\Exception;

class FloatType extends AbstractType
{

	public function validate(&$value)
	{
		if ( ! is_numeric($value)) {
			throw new Exception\ValidationFailure("Is not numeric");
		}
		
		$value = (float) $value;
	}

}
