<?php

namespace Supra\Controller\Pages\Event;

use Supra\Event\EventArgs;
use Supra\User\Entity\User;

class CmsUserCreateEventArgs extends EventArgs
{
	
	/**
	 * @var Supra\User\Entity\User
	 */
	public $user;
	
	/**
	 * @param User $user
	 */
	public function __construct(User $user) 
	{
		$this->user = $user;
	}
	
	/**
	 * @return Supra\User\Entity\User
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

}
