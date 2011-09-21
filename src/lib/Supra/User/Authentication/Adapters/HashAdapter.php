<?php

namespace Supra\User\Authentication\Adapters;

use Supra\User\Authentication\AuthenticationAdapterInterface;
use Supra\User\Entity\User;
use Supra\User\Exception;

/**
 * Adapter with email as login and password sha1 hash validation
 */
class HashAdapter implements AuthenticationAdapterInterface
{
	/**
	 * Finds user in database
	 * @param type $login
	 * @param type $password
	 * @return User 
	 */
	public function findUser($login, $password)
	{
		
	}

	/**
	 * Authenticates user
	 * @param User $user
	 * @param string $password
	 * @return boolean 
	 */
	public function authenticate(User $user, $password)
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
	 * @param type $password
	 * @param type $salt
	 * @return type 
	 */
	protected function generatePasswordHash($password, $salt)
	{
		if (empty($salt)) {
			throw new Exception\RuntimeException("User password salt is not permitted to be empty");
		}
		
		$hash = sha1($password . $salt);
		
		return $hash;
	}
	
	/**
	 * {@inheritdoc}
	 * @param User $user
	 * @param string $password
	 */
	public function credentialChange(User $user, $password = null)
	{
		// Email is login for this 
		$user->setLogin($user->getEmail());
		
		if ( ! is_null($password)) {
			
			if (empty($password)) {
				throw new Exception\PasswordPolicyException("Empty password is not allowed");
			}
			
			$salt = $user->getSalt();
			$passHash = $this->generatePasswordHash($password, $salt);
			$user->setPassword($passHash);
		}
	}

}