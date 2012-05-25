<?php

namespace Supra\User\Entity;

use Supra\Database\Entity;
use Supra\User\Exception;

/**
 * Single user preference item
 * @Entity
 */
class UserPreference extends Entity
{

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $name;
	
	/**
	 * @Column(type="array", nullable=true)
	 * @var string
	 */
	protected $value;
	
	/**
	 * @ManyToOne(targetEntity="Supra\User\Entity\User", inversedBy="preferences")
	 * @var User
	 */
	protected $user;
	
	
	/**
	 * @param string $name
	 * @param mixed $value
	 * @param User $user
	 */
	public function __construct($name, $value, User $user)
	{
		parent::__construct();
		
		$this->name = $name;
		$this->value = $value;
		$this->user = $user;
	}
	
	/**
	 * @return User
	 */
	public function getUser()
	{
		return $this->user;
	}
	
	/**
	 * @return string
	 */
	public function getName() 
	{
		return $this->name;
	}
	
	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}
	
	/**
	 * @param mixed $value
	 */
	public function setValue($value) 
	{
		$this->value = $value;
	}
	
	/**
	 * @param User $user
	 */
	public function setUser(User $user) 
	{
		$this->user = $user;
	}
}
