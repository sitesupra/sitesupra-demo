<?php

namespace Supra\Package\CmsAuthentication\Entity;

use Supra\Package\Cms\Entity\Abstraction\Entity;
use Supra\Package\Cms\Entity\Abstraction\TimestampableInterface;

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"user" = "User", "group" = "Group"})
 * @Table(name="user_abstraction", indexes={
 *		@index(name="user_abstraction_name_idx", columns={"name"})
 * })
 * @HasLifecycleCallbacks
 */
abstract class AbstractUser extends Entity implements TimestampableInterface
{
	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $name;
	
	/**
	 * @Column(type="datetime")
	 * @var \DateTime
	 */
	protected $creationTime;
	
	/**
	 * @Column(type="datetime")
	 * @var \DateTime
	 */
	protected $modificationTime;

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * Returns creation time
	 * @return \DateTime
	 */
	public function getCreationTime()
	{
		return $this->creationTime;
	}
	
	/**
	 * Sets creation time
	 * @param \DateTime $time
	 */
	public function setCreationTime(\DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new \DateTime();
		}
		$this->creationTime = $time;
	}

	/**
	 * Returns last modification time
	 * @return \DateTime
	 */
	public function getModificationTime()
	{
		return $this->modificationTime;
	}

	/**
	 * Sets modification time to now
	 * @param \DateTime $time
	 */
	public function setModificationTime(\DateTime $time = null)
	{
		$this->modificationTime = $time ? $time : new \DateTime();
	}

	/**
	 * Returns whether the user/group has SUPER privileges.
	 * @return boolean
	 */
	abstract function isSuper();
}