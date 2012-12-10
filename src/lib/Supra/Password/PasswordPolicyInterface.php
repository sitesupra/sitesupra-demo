<?php

namespace Supra\Password;

use Supra\Authentication\AuthenticationPassword;
use Supra\User\Entity\User;

interface PasswordPolicyInterface
{
	
	/**
	 * Validates user password
	 * 
	 * @param \Supra\Authentication\AuthenticationPassword $password
	 * @param \Supra\User\Entity\User $user
	 */
	public function validate(AuthenticationPassword $password, User $user);
	
	/**
	 * Validates user password expiration,
	 * should throw PasswordPolicyException in case of expired passwords
	 * 
	 * @param \Supra\User\Entity\User $user
	 */
	public function validateUserPasswordExpiration(User $user);
	
}
