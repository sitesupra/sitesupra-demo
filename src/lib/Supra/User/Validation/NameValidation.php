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
		
		// Length
		$nameLength = strlen($name);

		if ($nameLength <= 3) {
			throw new Exception\RuntimeException('Name should be longer than 3 symbols');
		}
		
		// Allowed characters
		$pattern = '/^[a-z0-9\_]+$/i';
		$alphanumeric = preg_match($pattern, $name, $alphanumeric);

		if ( ! $alphanumeric) {
			throw new Exception\RuntimeException('Name can contain only alphanumeric symbols and underscores');
		}

		// No double underscores
		$doubleUnderscores = strpos($name, '__');

		if ($doubleUnderscores !== false) {
			throw new Exception\RuntimeException('Name cannot contain consecutive underscore characters');
		}
			
	}

}
