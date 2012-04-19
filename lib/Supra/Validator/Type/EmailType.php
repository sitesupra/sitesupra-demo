<?php

namespace Supra\Validator\Type;

use Supra\Validator\Exception;

/**
 * Email validation type
 */
class EmailType extends AbstractType
{
	public function validate(&$value)
	{
		$valid = filter_var($value, FILTER_VALIDATE_EMAIL);
		
		if ( ! $valid) {
			throw new Exception\ValidationFailure("Email address not valid");
		}
	}
}
