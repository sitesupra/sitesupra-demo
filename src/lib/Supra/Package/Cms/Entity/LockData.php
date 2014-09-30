<?php

namespace Supra\Package\Cms\Entity;

use Supra\Database\Doctrine\Listener\Timestampable;

/**
 * Lock data class.
 * @Entity
 * @Table(name="lock_data")
 */
class LockData extends Abstraction\Entity implements Timestampable
{
	/**
	 * @Column(type="datetime", nullable=true)
	 * @var \DateTime
	 */
	protected $creationTime;
	
	/**
	 * @Column(type="datetime", nullable=true)
	 * @var \DateTime
	 */
	protected $modificationTime;
	
	/**
	 * @Column(type="supraId20")
	 * @var string
	 */
	protected $userId;

	/**
	 * @Column(type="string",nullable=true)
	 * @var string
	 */
	protected $pageRevision = '';
	
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
	 * Sets modification time
	 * @param \DateTime $time
	 */
	public function setModificationTime(\DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new \DateTime();
		}
		$this->modificationTime = $time;
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

	/**
	 * Set page data revision
	 * @param string $revisionId
	 */
	public function setPageRevision($revisionId)
	{
		$this->pageRevision = $revisionId;
	}

	/**
	 * Get page data revision
	 * @return string
	 */
	public function getPageRevision()
	{
		return $this->pageRevision;
	}

}
