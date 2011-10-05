<?php

namespace Supra\Authentication\Adapter;

use Supra\User\Entity\User;
use Supra\User\Exception;
use Supra\Authentication\AuthenticationPassword;

/**
 * Adapter with email as login and password sha1 hash validation
 */
class HashAdapter implements AuthenticationAdapterInterface
{
	/**
	 * Finds user in database
	 * @param string $login
	 * @param AuthenticationPassword $password
	 * @return User 
	 */
	public function findUser($login, AuthenticationPassword $password)
	{
		
	}

	/**
	 * Authenticates user
	 * @param User $user
	 * @param AuthenticationPassword $password
	 * @return boolean 
	 */
	public function authenticate(User $user, AuthenticationPassword $password)
	{
		if ( ! $user instanceof User) {
			throw new Exception\RuntimeException('User is not an instance of User entity');
		}
		
		$salt = $user->getSalt();
		$hash = $this->generatePasswordHash($password, $salt);
		
		$userPassword = $user->getPassword();
		
		if($hash != $userPassword) {
			throw new Exception\AuthenticationExeption('Wrong password entered');
		}
		
		return true;
		
	}
		
	/**
	 * Generates password for database
	 * @param AuthenticationPassword $password
	 * @param string $salt
	 * @return string
	 */
	protected function generatePasswordHash(AuthenticationPassword $password, $salt)
	{
		if (empty($salt)) {
			throw new Exception\RuntimeException("User password salt is not permitted to be empty");
		}
		
		$hash = sha1((string) $password . $salt);
		
		return $hash;
	}
	
	/**
	 * {@inheritdoc}
	 * @param User $user
	 * @param AuthenticationPassword $password
	 */
	public function credentialChange(User $user, AuthenticationPassword $password = null)
	{
		// Email is login for this 
		$user->setLogin($user->getEmail());
		
		if ( ! is_null($password)) {
			
			if ($password->isEmpty()) {
				throw new Exception\PasswordPolicyException("Empty password is not allowed");
			}
			
			$salt = $user->getSalt();
			$passHash = $this->generatePasswordHash($password, $salt);
			$user->setPassword($passHash);
		}
	}

}