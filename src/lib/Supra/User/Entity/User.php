<?php

namespace Supra\User\Entity;

/**
 * User object
 * @Entity
 * @Table(name="user")
 */
class User extends Abstraction\User
{
	
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var integer
	 */
	protected $id = null;
	
	/**
	 * @Column(type="string", name="password", nullable=true)
	 * @var string
	 */
	protected $password;
	
	/**
	 * @Column(type="string", name="email", nullable=false, unique=true)
	 * @var string
	 */
	protected $email;
	
	/**
	* @OneToOne(targetEntity="Group")
	* @JoinColumn(name="group_id", referencedColumnName="id") 
	 */
	
	protected $group;
	
	/**
	 * @Column(type="datetime", name="last_login_at", nullable="false")
	 * @var string
	 */
	
	protected $lastLoginTime;
	
	/**
	 * @Column(type="boolean", name="active")
	 * @var string
	 */
	protected $active = true;
	
	/**
	 * @Column(type="string", name="salt", nullable=false)
	 * @var string
	 */
	protected $salt;
	
	public function __construct()
	{
//		$this->createdTime = new \DateTime("now");
	}
	
	/**
	 * Returns user id 
	 * @return integer 
	 */
	public function getId()
	{
		return $this->id;
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
	 * @param type $password 
	 */
	public function setPassword($password)
	{
		$this->password = $password;
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
	 * Returns user last logged in time 
	 * @return datetime 
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
		$this->salt = uniqid();
		
		return $this->salt;
	}
	
	public function getGroup()
	{
		return $this->group;
	}

	public function setGroup($group)
	{
		$this->group = $group;
	}


}
