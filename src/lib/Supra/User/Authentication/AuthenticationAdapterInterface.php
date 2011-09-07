<?php

namespace Supra\User\Authentication;

use Supra\User\Entity\User;

interface AuthenticationAdapterInterface
{
	/**
	 * Authenticate user
	 */
	public function authenticate(User $user, $password);
	
	/**
	 * Find user 
	 */
	public function findUser($login, $password);
	
	/**
	 * change password
	 */
	public function changePassword(User $user, $password);

}