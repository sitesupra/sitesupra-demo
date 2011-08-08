<?php

namespace Supra\User\Validation;

use Supra\User;
use Supra\User\Exception;

/**
 * User Email validation
 */
class NameValidation implements UserValidationInterface
{

	public function validateUser(\Supra\User\Entity\User $user)
	{
		$name = $user->getName();
		$pattern = '/[^a-z0-9\_]/';
		$nameLength = strlen($name);

		if ($nameLength > 3) {

			$alphanumeric = preg_match($pattern, $name, $alphanumeric);
			$doubleUnderscores = preg_match('/\_\_/', $name, $doubleUnderscores);

			if ( ! empty($alphanumeric) || ! empty($doubleUnderscores)) {
				throw new Exception\RuntimeException('Name can contain only alphanumeric symbols and underscore');
			}
			
		} else {
			throw new Exception\RuntimeException('Name should be longer than 3 symbols');
		}
	}

}
