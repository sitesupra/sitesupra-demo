<?php

namespace Supra\User\Entity;

/**
 * User object
 * @Entity
 * @Table(name="su_user")
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
	 * @Column(type="string", name="password", nullable=false)
	 * @var string
	 */
	protected $password;
	
	/**
	 * @Column(type="string", name="email", nullable=false)
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

	/**
	 * Returns user creation time
	 * @return datetime
	 */
	public function getCreatedTime()
	{
		return $this->createdTime;
	}

	/**
	 * Sets user creation time
	 * @param \DateTime $createdTime 
	 */
	public function setCreatedTime(\DateTime $createdTime)
	{
		$this->createdTime = $createdTime;
	}

	/**
	 * Returns user last modification time
	 * @return type 
	 */
	public function getModifiedTime()
	{
		return $this->modifiedTime;
	}

	/**
	 * sets user last modification time
	 * @param \DateTime $modifiedTime 
	 */
	public function setModifiedTime(\DateTime $modifiedTime)
	{
		$this->modifiedTime = $modifiedTime;
	}

	/**
	 * Returns user status
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


}
