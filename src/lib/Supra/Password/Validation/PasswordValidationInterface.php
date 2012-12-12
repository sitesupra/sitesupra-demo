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
}
