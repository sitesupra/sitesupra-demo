<?php

namespace Supra\User\Entity;

use Supra\ObjectRepository\ObjectRepository;
use Supra\User\Entity\User as RealUser;
use Supra\Authorization\AuthorizedEntityInterface;
use Supra\User\Entity\Abstraction\User as AbstractUser;
use Supra\Authorization\AuthorizationProvider;

/**
 * Group object
 * @Entity
 * @Table(name="group") 
 */
class Group extends Abstraction\User implements AuthorizedEntityInterface
{

	/**
	 * @Column(type="boolean", name="is_super")
	 * @var boolean
	 */
	protected $isSuper = false;

	/**
	 * {@inheritDoc}
	 */
	public function isSuper()
	{

		return $this->isSuper;
	}

	/**
	 * Sets groups SUPER privilege status
	 * @param boolean $isSuper 
	 */
	public function setIsSuper($isSuper)
	{

		$this->isSuper = $isSuper;
	}

	/**
	 * Returns itself.
	 * @return Group 
	 */
	public function getGroup()
	{
		return $this;
	}

}
