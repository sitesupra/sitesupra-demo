<?php

namespace Project\SampleAuthentication;

use Supra\Authentication\AuthenticationSessionNamespace;
use Supra\User\Entity\User;
use Supra\ObjectRepository\ObjectRepository;

class AuthenticateSessionNamespace extends AuthenticationSessionNamespace
{
	/**
	 * Returns user from session
	 * @return User 
	 */
	public function getUser()
	{
		$userId = $this->__data['userId'];
		
		if (empty ($userId)) {
			return null;
		}
		
		$userProvider = ObjectRepository::getUserProvider($this);
		$user = $userProvider->findUserById($userId);
		
		return $user;
	}
	
	/**
	 * Sets user id into session
	 * @param User $user 
	 */
	public function setUser(User $user)
	{
		$userId = $user->getId();
		$this->__data['userId'] = $userId; 
	}
	
	/**
	 * Removes user id from session session
	 */
	public function removeUser()
	{
		$this->__data['userId'] = null; 
	}
	
}