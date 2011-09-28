<?php

namespace Supra\Controller\Pages\Entity;

/**
 * Lock data class.
 * @Entity
 * @Table(name="lock_data")
 * @HasLifecycleCallbacks
 */
class LockData extends Abstraction\Entity
{
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var integer
	 */
	protected $id;
	
	/**
	 * @Column(type="datetime", nullable=true, name="created_at")
	 * @var DateTime
	 */
	protected $created;
	
	/**
	 * @Column(type="datetime", nullable=true, name="modified_at")
	 * @var DateTime
	 */
	protected $modified;
	
	/**
	 * @Column(type="sha1")
	 * @var string
	 */
	protected $userId;
	
	/**
	 * Returns lock ID
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * Returns creation time
	 * @return \DateTime
	 */
	public function getCreatedTime()
	{
		return $this->created;
	}
	
	/**
	 * Sets creation time to now
	 * @PrePersist
	 */
	public function setCreatedTime()
	{
		$this->created = new \DateTime('now');
	}

	/**
	 * Returns last modification time
	 * @return \DateTime
	 */
	public function getModifiedTime()
	{
		return $this->modified;
	}

	/**
	 * Sets modification time to now 
	 * @PreUpdate
	 * @PrePersist
	 */
	public function setModifiedTime()
	{
		$this->modified = new \DateTime('now');
	}
	
	/**
	 * Returns ID of user created the lock
	 * @return string
	 */
	public function getUserId()
	{
		return $this->userId;
	}
	
	/**
	 * Sets ID of user who's locking page
	 * @param string $userId
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;
	}

}
