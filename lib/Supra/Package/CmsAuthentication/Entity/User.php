<?php

namespace Supra\Package\CmsAuthentication\Entity;

use Supra\Core\Locale\LocaleInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * User object
 * @Entity(repositoryClass="UserRepository")
 * @Table(name="user")
 */
class User extends AbstractUser implements UserInterface
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
	 * @Column(type="boolean", name="email_confirmed", nullable=true)
	 * @var boolean
	 */
	protected $emailConfirmed;

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
	 * @ManyToOne(targetEntity="Group", fetch="EAGER")
	 * @JoinColumn(name="group_id", referencedColumnName="id")
	 */
	protected $group;

	/**
	 * @Column(type="datetime", name="last_login_at", nullable=true)
	 * @var \DateTime
	 */
	protected $lastLoginTime;

	/**
	 * @Column(type="boolean", name="active")
	 * @var boolean
	 */
	protected $active = true;

	/**
	 * @Column(type="string", nullable=false, length=23)
	 * @var string
	 */
	protected $salt;

	/**
	 * User settings collection
	 * @OneToOne(targetEntity="Supra\Package\CmsAuthentication\Entity\UserPreferencesCollection", cascade={"all"})
	 * @JoinColumn(name="preferences_collection_id", referencedColumnName="id", nullable=true)
	 * @var UserPreferencesCollection
	 */
	protected $preferencesCollection;

	/**
	 * Users locale. Semi-synthetic, as setter/getter uses Locale class instances.
	 * @Column(type="string", nullable=true, length=40)
	 * @var string
	 */
	protected $localeId;

	/**
	 * @Column(type="string", name="status", nullable=true)
	 * @var string
	 */
	protected $status;
	
	/**
	 * @Column(type="datetime", nullable=true)
	 * @var \DateTime
	 */
	protected $lastPasswordChangeTime;
	
	/**
	 * @Column(type="boolean")
	 * @var boolean
	 */
	protected $forcePasswordChange = false;

	/**
	 * @Column(type="array")
	 * @var array
	 */
	protected $roles;

	/**
	 * Generates random salt for new users
	 */
	public function __construct()
	{
		parent::__construct();
		$this->resetSalt();

		$this->preferencesCollection = new UserPreferencesCollection();
	}

	/**
	 * @param array $roles
	 */
	public function setRoles($roles)
	{
		$this->roles = $roles;
	}

	/**
	 * @return array
	 */
	public function getRoles()
	{
		return $this->roles;
	}

	/**
	 * See UserInterface
	 */
	public function eraseCredentials()
	{
		$this->roles = array();
	}

	/**
	 * Alias for UserInterface
	 *
	 * @return string
	 */
	public function getUsername()
	{
		return $this->getLogin();
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
		
		// password expiration related
		$this->lastPasswordChangeTime = new \DateTime('now');
		$this->forcePasswordChange = false;
	}
	
	/**
	 * Get user last password change datetime
	 * @return \DateTime|null
	 */
	public function getLastPasswordChangeTime()
	{
		return $this->lastPasswordChangeTime;
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
	 */
	public function setAvatar($avatarId)
	{
		$this->avatarId = $avatarId;
		$this->personalAvatar = false;
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
	 * @param \DateTime $time
	 */
	public function setLastLoginTime(\DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new \DateTime();
		}
		$this->lastLoginTime = $time;
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
	 * Returns is user email confirmed
	 * @return bool
	 */
	public function getEmailConfirmed()
	{
		return $this->emailConfirmed;
	}

	/**
	 * Sets is user email confirmed
	 * @param bool $confirmed 
	 */
	public function setEmailConfirmed($confirmed)
	{
		$this->emailConfirmed = (bool) $confirmed;
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
	 * @param LocaleInterface $locale
	 */
	public function setLocale(LocaleInterface $locale)
	{
		$this->localeId = $locale->getId();
	}

	/**
	 * @return LocaleInterface
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
		} else {
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

	public function eatSibling(User $sibling)
	{
		$userData = get_object_vars($sibling);

		$this->fillFromArray($userData);
	}

	/**
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
 	 * @param string $status
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}

	public function fillFromArray(array $userData)
	{
		$this->id = $userData['id'];
		$this->password = $userData['password'];
		$this->login = $userData['login'];
		$this->email = $userData['email'];
		$this->name = $userData['name'];
		$this->avatarId = $userData['avatarId'];
		$this->personalAvatar = $userData['personalAvatar'];
		$this->group = $userData['group'];
		$this->lastLoginTime = $userData['lastLoginTime'];
		$this->active = $userData['active'];
		
		$this->salt = $userData['salt'];
		//$this->userSessions = $userData['userSessions'];

		$this->localeId = $userData['localeId'];
		
		$this->lastPasswordChangeTime = $userData['lastPasswordChangeTime'];
	}

	public static function getAlias()
	{
		return 'user';
	}

	/**
	 * @return UserPreferencesCollection
	 */
	public function getPreferencesCollection()
	{
		return $this->preferencesCollection;
	}

	/**
	 * @param UserPreferencesCollection $preferencesCollection
	 */
	public function setPreferencesCollection($preferencesCollection)
	{
		$this->preferencesCollection = $preferencesCollection;
	}

	/**
	 * @return string
	 */
	public function getGravatarUrl($size = 48, $protocol = 'http')
	{
		$defaultImageset = 'identicon'; // [ 404 | mm | identicon | monsterid | wavatar ]
		//$size = 48; // Size in pixels
		$maxAllowedDecencyRating = 'g'; // [ g | pg | r | x ]
        
        $url = $protocol . '://www.gravatar.com/avatar/';
		$url .= md5(strtolower(trim($this->getEmail())));
		$url .= "?s=$size&d=$defaultImageset&r=$maxAllowedDecencyRating";

		return $url;
	}
	
	/**
	 * Set flag, which used to force user to change password 
	 * related to password expiration feature
	 */
	public function forcePasswordChange()
	{
		$this->forcePasswordChange = true;
	}
	
	/**
	 * Check weither current user is forced to change his password
	 * 
	 * @return boolean
	 */
	public function isForcedToChangePassword()
	{
		return ($this->forcePasswordChange === true);
	}

}
