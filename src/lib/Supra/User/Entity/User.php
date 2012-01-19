<?php

namespace Supra\User\Entity;

use Doctrine\Common\Collections\Collection;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Locale\Locale;
use Supra\Cms\InternalUserManager\Useravatar\UseravatarAction;

/**
 * User object
 * @Entity
 * @Table(name="user")
 */
class User extends AbstractUser
{

	/**
	 * @Column(type="string", name="password", nullable=true)
	 * @var string
	 */
	protected $password;

	/**
	 * @Column(type="string", name="login", nullable=false, unique=true)
	 * @var string
	 */
	protected $login;

	/**
	 * @Column(type="string", name="email", nullable=false, unique=true)
	 * @var string
	 */
	protected $email;

	/**
	 * @Column(type="string", name="avatar_id", nullable=true)
	 * @var string
	 */
	protected $avatarId;

	/**
	 * @Column(type="boolean", name="personal_avatar", nullable=true)
	 * @var boolean
	 */
	protected $personalAvatar;
	
	/**
	 * @ManyToOne(targetEntity="Group")
	 * @JoinColumn(name="group_id", referencedColumnName="id")
	 */
	protected $group;

	/**
	 * @Column(type="datetime", name="last_login_at", nullable="false")
	 * @var \DateTime
	 */
	protected $lastLoginTime;

	/**
	 * @Column(type="boolean", name="active")
	 * @var boolean
	 */
	protected $active = true;

	/**
	 * @Column(type="string", nullable=false, length="23")
	 * @var string
	 */
	protected $salt;

	/**
	 * Added only to cascade removal
	 * @OneToMany(targetEntity="UserSession", mappedBy="user", cascade={"remove"})
	 * @var Collection
	 */
	protected $userSessions;

	/**
	 * Users locale. Semi-synthetic, as setter/getter uses Locale class instances.
	 * @Column(type="string", nullable=true, length="40")
	 * @var string
	 */
	protected $localeId;

	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $facebookId;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $facebookAccessToken;
	
	/**
	 * Generates random salt for new users
	 */
	public function __construct()
	{
		parent::__construct();
		$this->resetSalt();
	}

	/**
	 * Returns user password
	 * @return type 
	 */
	public function getPassword()
	{
		return $this->password;
	}

	/**
	 * Sets user password
	 * @param string $password 
	 */
	public function setPassword($password)
	{
		$this->password = $password;
	}

	/**
	 * @return string
	 */
	public function getLogin()
	{
		return $this->login;
	}

	/**
	 * @param string $login 
	 */
	public function setLogin($login)
	{
		$this->login = $login;
	}

	/**
	 * Returns user email 
	 * @return string 
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * Sets user email
	 * @param string $email 
	 */
	public function setEmail($email)
	{
		$this->email = $email;
	}

	/**
	 * @return string
	 */
	public function getAvatar()
	{
		return $this->avatarId;
	}

	/**
	 * @param string $avatarId - Predefined avatar id. One of UseravatarAction::$sampleAvatars
	 * @return boolean
	 */
	public function setAvatar($avatarId)
	{
		if(in_array($avatarId, UseravatarAction::getPredefinedAvatarIds())) {
			$this->avatarId = $avatarId;
			return true;
		}
		return false;
	}

	/**
	 * Returns user last logged in time 
	 * @return \DateTime 
	 */
	public function getLastLoginTime()
	{
		return $this->lastLoginTime;
	}

	/**
	 * Sets user last logged in time 
	 * @param \DateTime $lastLoginTime
	 */
	public function setLastLoginTime(\DateTime $lastLoginTime)
	{
		$this->lastLoginTime = $lastLoginTime;
	}

	/**
	 * Get if the user is active
	 * @return boolean
	 */
	public function isActive()
	{
		return $this->active;
	}

	/**
	 * Sets user status
	 * @param boolean $active 
	 */
	public function setActive($active)
	{
		$this->active = $active;
	}

	/**
	 * Returns salt
	 * @return string 
	 */
	public function getSalt()
	{
		return $this->salt;
	}

	/**
	 * Resets salt and returns
	 * @return string
	 */
	public function resetSalt()
	{
		// Generates 23 character salt
		$this->salt = uniqid('', true);

		return $this->salt;
	}

	/**
	 * @return Entity\Group
	 */
	public function getGroup()
	{
		return $this->group;
	}

	/**
	 * @param Entity\Group $group
	 */
	public function setGroup($group)
	{
		$this->group = $group;
	}

	/**
	 * @param Locale $locale 
	 */
	public function setLocale(Locale $locale)
	{
		$this->localeId = $locale->getId();
	}

	/**
	 * @return Locale
	 */
	public function getLocale()
	{
		$localeManager = ObjectRepository::getLocaleManager($this);

		$locale = $localeManager->getLocale($this->localeId);

		return $locale;
	}

	/**
	 * {@inheritDoc}
	 */
	public function isSuper()
	{
		if ( ! is_null($this->getGroup())) {
			return $this->getGroup()->isSuper();
		}
		else {
			return false;
		}
	}
	
	/**
	 * @return boolean
	 */
	public function hasPersonalAvatar()
	{
		return $this->personalAvatar;
	}

	/**
	 * @param boolean $personalAvatar 
	 */
	public function setPersonalAvatar($personalAvatar)
	{
		$this->personalAvatar = $personalAvatar;
	}

	public function getFacebookId()
	{
		return $this->facebookId;
	}

	public function setFacebookId($facebookId)
	{
		$this->facebookId = $facebookId;
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
