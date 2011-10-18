<?php

namespace Supra\Authentication;

use Supra\Session\SessionNamespace;
use Supra\ObjectRepository\ObjectRepository;
use Supra\User\Entity\User;

/**
 * Session namespace for authentication result
 */
class AuthenticationSessionNamespace extends SessionNamespace
{
	const USER_ID_KEY = 'userId';

	/**
	 * Returns user from session
	 * @return User 
	 */
	public function getUser()
	{
		if (empty($this->__data[self::USER_ID_KEY])) {
			return null;
		}

		$userId = $this->__data[self::USER_ID_KEY];

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
		$this->__data[self::USER_ID_KEY] = $userId;
	}

	/**
	 * Removes user id from session session
	 */
	public function removeUser()
	{
		$this->__data[self::USER_ID_KEY] = null;
	}

}
