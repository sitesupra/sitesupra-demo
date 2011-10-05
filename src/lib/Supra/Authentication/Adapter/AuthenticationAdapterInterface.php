<?php

namespace Supra\Authentication\Adapter;

use Supra\User\Entity\User;
use Supra\Authentication\AuthenticationPassword;

/**
 * Authentication adapter interface
 */
interface AuthenticationAdapterInterface
{
	/**
	 * Authenticate the user
	 * @param User $user
	 * @param AuthenticationPassword $password
	 */
	public function authenticate(User $user, AuthenticationPassword $password);
	
	/**
	 * Try searching for the user if adapter implements this
	 * @param string $login
	 * @param AuthenticationPassword $password
	 */
	public function findUser($login, AuthenticationPassword $password);
	
	/**
	 * Called on credential change (password, login)
	 * @param User $user
	 * @param AuthenticationPassword $password
	 */
	public function credentialChange(User $user, AuthenticationPassword $password = null);
}