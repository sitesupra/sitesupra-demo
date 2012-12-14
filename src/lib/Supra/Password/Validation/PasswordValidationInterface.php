<?php

namespace Supra\Password\Validation;

use Supra\Authentication\AuthenticationPassword;
use Supra\User\Entity\User;

interface PasswordValidationInterface
{
	/**
	 * Validates the password
	 * 
	 * @param AuthenticationPassword $password
	 * @throws \Supra\Password\Exception\PasswordPolicyException
	 */
	public function validatePassword(AuthenticationPassword $password, User $user);
	
	/**
	 * Returns validator requirements which is shown 
	 * as one of the requirement inside password change form
	 * 
	 * @return string
	 */
	public function getFilterRequirements();
}
