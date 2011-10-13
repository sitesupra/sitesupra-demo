<?php

namespace Supra\User\Entity;

use Supra\Database\Entity;
use DateTime;
use Supra\Database\Doctrine\Listener\Timestampable;

/**
 * User session entity
 * @Entity
 */
class UserSession extends Entity implements Timestampable
{
	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $creationTime;
	
	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $lastActivityTime;

	/**
	 * @return DateTime
	 */
	public function getCreationTime()
	{
		return $this->creationTime;
	}

	/**
	 * @return DateTime
	 */
	public function getModificationTime()
	{
		return $this->lastActivityTime;
	}

	/**
	 * @param DateTime $time
	 */
	public function setCreationTime(DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new DateTime;
		}
		$this->creationTime = $time;
	}

	/**
	 * @param DateTime $time
	 */
	public function setModificationTime(DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new DateTime;
		}
		$this->lastActivityTime = $time;
	}
}
