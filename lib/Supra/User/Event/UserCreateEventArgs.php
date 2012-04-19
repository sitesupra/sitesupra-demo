<?php

namespace Supra\User\Event;

use Supra\Event\EventArgs;
use Supra\User\Entity\User;
use Supra\User\UserProviderInterface;

class UserCreateEventArgs extends EventArgs
{
	
	/**
	 * @var User
	 */
	public $user;
	
	/**
	 * @var UserProviderInterface
	 */
	public $userProvider;
	
	/**
	 * @return User
	 */
	public function getUser()
	{
		return $this->user;
	}
	
	/**
	 * @param User $user
	 */
	public function setUser(User $user)
	{
		$this->user = $user;
	}
	
	/**
	 * @param UserProviderInterface $userProvider
	 */
	public function setUserProvider(UserProviderInterface $userProvider) 
	{
		$this->userProvider = $userProvider;
	}
	
	/**
	 * @return UserProviderInterface
	 */
	public function getUserProvider()
	{
		return $this->userProvider;
	}

}
