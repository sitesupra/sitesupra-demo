<?php

namespace Project\AutoregisterAuthenticationAdapter;

use Supra\Authentication\Adapter\HashAdapter;
use Supra\User\Entity\User;
use Supra\Authentication\AuthenticationPassword;

/**
 * Development authentication adapter automatically registering new users
 */
class AutoregisterAuthenticationAdapter extends HashAdapter
{
	/**
	 * Creates new user using credentials entered in the login form
	 * @param string $login
	 * @param AuthenticationPassword $password
	 * @return User
	 */
	public function findUser($login, AuthenticationPassword $password)
	{
		$user = new User();
		$user->setName($login);
		$user->setEmail($login . '@supra7.vig');
		
		$this->credentialChange($user, $password);
		
		return $user;
	}
	
	/**
	 * Overriden method to keep login inact
	 * @param User $user
	 * @param AuthenticationPassword $password
	 */
	public function credentialChange(User $user, AuthenticationPassword $password = null)
	{
		$login = $user->getLogin();
		
		parent::credentialChange($user, $password);
		
		if ( ! empty($login)) {
			$user->setLogin($login);
		}
	}
}
