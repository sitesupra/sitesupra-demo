<?php

namespace Supra\User\Authentication\Adapters;

use Supra\User\Authentication\AuthenticationAdapterInterface;
use Supra\User\Entity\User;
use Supra\ObjectRepository\ObjectRepository;

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
	public function generatePasswordHash($password, $salt)
	{
		return sha1($password . $salt); 
	}
	
	public function changePassword($login, $password)
	{
		
	}

}