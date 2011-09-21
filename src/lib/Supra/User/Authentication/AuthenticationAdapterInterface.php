<?php

namespace Supra\User\Authentication;

use Supra\User\Entity\User;

/**
 * Authentication adapter interface
 */
interface AuthenticationAdapterInterface
{
	/**
	 * Authenticate the user
	 * @param User $user
	 * @param string $password
	 */
	public function authenticate(User $user, $password);
	
	/**
	 * Try searching for the user if adapter implements this
	 * @param string $login
	 * @param string $password
	 */
	public function findUser($login, $password);
	
	/**
	 * Called on credential change (password, login)
	 * @param User $user
	 * @param string $password
	 */
	public function credentialChange(User $user, $password = null);
}