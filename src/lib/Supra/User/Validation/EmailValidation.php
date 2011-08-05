<?php


namespace Supra\User\Validation;

use Supra\User;
use Supra\User\Exception;

/**
 * User Email validation
 */
class EmailValidation implements UserValidationInterface
{
	
	public function validateUser(\Supra\User\Entity\User $user)
	{
		$email = $user->getEmail();
		$result = filter_var($email, FILTER_VALIDATE_EMAIL);
		
		if(empty($result)) {
			throw new Exception\RuntimeException('Email isn\'t valid');
		}
	}

}

?>
