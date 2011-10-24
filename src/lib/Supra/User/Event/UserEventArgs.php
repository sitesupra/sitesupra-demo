<?php

namespace Supra\User\Event;

use Supra\Event\EventArgs;
use Doctrine\ORM\EntityManager;
use Supra\User\Entity\User;

/**
 * User provider event argument object
 */
class UserEventArgs extends EventArgs
{
	/**
	 * @var EntityManager
	 */
	public $entityManager;
	
	/**
	 * @var User
	 */
	public $user;
}
