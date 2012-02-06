<?php

namespace Supra\User\Entity;

use Doctrine\Common\Collections\Collection;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Database\Entity;
use Doctrine\Common\Collections;

/**
 * User facebook data
 * @Entity
 * @Table(name="user_facebook_data")
 */
class UserFacebookData extends Entity
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

	public function getUser()
	{
		return $this->user;
	}

	public function setUser(User $user)
	{
		$this->user = $user;
	}

	public function getFacebookUserId()
	{
		return $this->facebookUserId;
	}

	public function setFacebookUserId($facebookUserId)
	{
		$this->facebookUserId = $facebookUserId;
	}

	public function getFacebookAccessToken()
	{
		return $this->facebookAccessToken;
	}

	public function setFacebookAccessToken($facebookAccessToken)
	{
		$this->facebookAccessToken = $facebookAccessToken;
	}

}