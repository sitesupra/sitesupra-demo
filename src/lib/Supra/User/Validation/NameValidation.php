<?php

namespace Supra\User\Validation;

use Supra\User;
use Supra\User\Exception;
use Supra\User\Entity\User;

/**
 * User name validation
 */
class NameValidation implements UserValidationInterface
{
	/**
	 * {@inheritdoc}
	 * @param User $user
	 * @throws Exception\RuntimeException
	 */
	public function validateUser(User $user)
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
