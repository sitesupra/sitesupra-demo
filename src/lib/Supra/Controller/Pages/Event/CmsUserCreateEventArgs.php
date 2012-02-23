<?php

namespace Supra\Controller\Pages\Event;

use Supra\Event\EventArgs;
use Supra\User\Entity\User;
use Supra\User\UserProviderInterface;

class CmsUserCreateEventArgs extends EventArgs
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
	 * @param User $user
	 */
	public function __construct(User $user) 
	{
		$this->user = $user;
	}
	
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
