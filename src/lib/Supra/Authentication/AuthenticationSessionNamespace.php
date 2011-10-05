<?php

namespace Supra\Authentication;

use Supra\Session\SessionNamespace;
use Supra\ObjectRepository\ObjectRepository;
use Supra\User\Entity\User;

/**
 * Session namespace for authentication result
 */
abstract class AuthenticationSessionNamespace extends SessionNamespace
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
