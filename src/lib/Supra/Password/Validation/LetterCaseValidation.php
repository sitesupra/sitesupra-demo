<?php

namespace Supra\Password\Validation;

use Supra\Authentication\AuthenticationPassword;
use Supra\User\Entity\User;
use Supra\Password\Exception;

/**
 * Password lower/upper case letter validation
 */
class LetterCaseValidation implements PasswordValidationInterface
{

	/**
	 * @return string
	 */
	public function getFilterRequirements()
	{
		return "Must contain a combination of upper and lower case letters.";
	}
	
	/**
	 * 
	 * @param \Supra\Authentication\AuthenticationPassword $password
	 * @param \Supra\User\Entity\User $user
	 * @throws Exception\PasswordPolicyException
	 */
	public function validatePassword(AuthenticationPassword $password, User $user)
	{
		$passwordString = $password->__toString();

		if (mb_strtoupper($passwordString) == $passwordString) {
			throw new Exception\PasswordPolicyException('Password must contain at least one lowercase letter');
		}
		
		if (mb_strtolower($passwordString) == $passwordString) {
			throw new Exception\PasswordPolicyException('Password must contain at least one uppercase letter');
		}
	}
}



