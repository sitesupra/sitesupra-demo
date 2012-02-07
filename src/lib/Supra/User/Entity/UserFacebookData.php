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

}