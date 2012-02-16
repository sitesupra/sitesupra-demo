<?php

namespace Supra\User\Entity;

use Doctrine\Common\Collections\Collection;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Database\Entity;
use Doctrine\Common\Collections;
use Supra\Database\Doctrine\Listener\Timestampable;
use DateTime;

/**
 * User facebook data
 * @Entity
 * @Table(name="user_facebook_data")
 */
class UserFacebookData extends Entity implements Timestampable
{

	/**
	 * @OneToOne(targetEntity="User")
	 * @JoinColumn(name="user_id", referencedColumnName="id")
	 * @var User
	 */
	protected $user;

	/**
	 * @Column(type="string", nullable=false, unique="true")
	 * @var string 
	 */
	protected $facebookUserId;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string 
	 */
	protected $facebookAccessToken;

	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $creationTime;

	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $modificationTime;

	/**
	 * @Column(type="boolean", nullable=false)
	 * @var boolean 
	 */
	protected $active = true;

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
	 * @return string 
	 */
	public function getFacebookUserId()
	{
		return $this->facebookUserId;
	}

	/**
	 *
	 * @param string $facebookUserId 
	 */
	public function setFacebookUserId($facebookUserId)
	{
		$this->facebookUserId = $facebookUserId;
	}

	/**
	 * @return string 
	 */
	public function getFacebookAccessToken()
	{
		return $this->facebookAccessToken;
	}

	/**
	 * @param string $facebookAccessToken 
	 */
	public function setFacebookAccessToken($facebookAccessToken)
	{
		$this->facebookAccessToken = $facebookAccessToken;
	}

	/**
	 * @return \DateTime 
	 */
	public function getCreationTime()
	{
		return $this->creationTime;
	}

	/**
	 * @return \DateTime  
	 */
	public function getModificationTime()
	{
		return $this->modificationTime;
	}

	public function setCreationTime(DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new DateTime();
		}
		$this->creationTime = $time;
	}

	public function setModificationTime(DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new DateTime();
		}
		$this->modificationTime = $time;
	}
	
	public function isActive()
	{
		return $this->active;
	}

	public function setActive($active)
	{
		$this->active = $active;
	}



}