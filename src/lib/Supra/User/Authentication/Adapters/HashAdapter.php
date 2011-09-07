<?php

namespace Supra\User\Authentication\Adapters;

use Supra\User\Authentication\AuthenticationAdapterInterface;
use Supra\User\Entity\User;
use Supra\ObjectRepository\ObjectRepository;
use Supra\User\Exception;

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
		if (empty($user) || ( ! $user instanceof User)) {
			return false;
		}
		
		$salt = $user->getSalt();
		$hash = $this->generatePasswordHash($password, $salt);
		
		$userPassword = $user->getPassword();
		
		if($hash != $userPassword) {
//			throw new Exception('Wrong password entered');
			return false;
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
	
	
	public function changePassword(User $user, $password)
	{
		$salt = $user->getSalt();
		$passHash = $this->generatePasswordHash($password, $salt);
		$user->setPassword($passHash);
	}

}