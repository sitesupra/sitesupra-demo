<?php

namespace Supra\Package\CmsAuthentication\Entity;

use Supra\Authorization\AuthorizedEntityInterface;
use Supra\Package\Cms\Entity\Abstraction\TimestampableInterface;

/**
 * Group object
 * @Entity
 * @Table(name="group") 
 */
class Group extends AbstractUser implements AuthorizedEntityInterface, TimestampableInterface
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
	
	/**
	 * @param array $groupData
	 */
	public function fillFromArray(array $groupData) 
	{
		$this->id = $groupData['id'];
		$this->creationTime = $groupData['creationTime'];
		$this->modificationTime = $groupData['modificationTime'];
		$this->isSuper = $groupData['isSuper'];
		$this->name = $groupData['name'];
	}

	public static function getAlias()
	{
		return 'user';
	}

}
