<?php

namespace Supra\User\Entity;

use Supra\Database\Entity;
use DateTime;
use Supra\Database\Doctrine\Listener\Timestampable;

/**
 * User session entity
 * @Entity
 */
class UserSession extends Entity implements Timestampable
{
	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $creationTime;
	
	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $lastActivityTime;
	
	/**
//	 * @ManyToOne(targetEntity="User", inversedBy="userSessions")
//	 * @var User
	 * 
	 * @Column(type="supraId20")
	 * @var string
	 */
	protected $user;
	
	protected $userObject;

	/**
	 * @return DateTime
	 */
	public function getCreationTime()
	{
		return $this->creationTime;
	}

	/**
	 * @return DateTime
	 */
	public function getModificationTime()
	{
		return $this->lastActivityTime;
	}

	/**
	 * @param DateTime $time
	 */
	public function setCreationTime(DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new DateTime;
		}
		$this->creationTime = $time;
	}

	/**
	 * @param DateTime $time
	 */
	public function setModificationTime(DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new DateTime;
		}
		$this->lastActivityTime = $time;
	}

	/**
	 * @return User
	 */
	public function getUser()
	{
		if ( ! is_null($this->userObject)) {
			return $this->userObject;
		}
		
		$userProvider = \Supra\ObjectRepository\ObjectRepository::getUserProvider($this);
		$user = $userProvider->findUserById($this->user);
		
		$this->userObject = $user;
		
		return $user;
	}

	/**
	 * @param User $user
	 */
	public function setUser(User $user)
	{
		$this->userObject = $user;
		$this->user = $user->getId();
	}
}
