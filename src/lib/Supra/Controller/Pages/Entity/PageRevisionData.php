<?php

namespace Supra\Controller\Pages\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Supra\Controller\Pages\Exception;
use Supra\Database\Doctrine\Listener\Timestampable;
use DateTime;

/**
 * Revision data class
 * @Entity
 */
class PageRevisionData extends Abstraction\Entity implements Timestampable
{
	
	const TYPE_HISTORY = 1;
	
	const TYPE_TRASH = 2;
	
	/**
	 * @Column(type="datetime", nullable=true, name="created_at")
	 * @var DateTime
	 */
	protected $creationTime;
	
	/**
	 * @Column(type="sha1")
	 * @var string
	 */
	protected $user;
	
	/**
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $type;
	
	/**
	 * Contains page or page localization ID
	 * 
	 * @Column(type="sha1")
	 * @var string
	 */
	protected $reference;
	
	/**
	 * Returns revision author
	 * @return string
	 */
	public function getUser()
	{
		return $this->user;
	}
	
	/**
	 * Sets revision author
	 * @param string $user 
	 */
	public function setUser($user)
	{
		$this->user = $user;
	}
	
	/**
	 * Returns creation time
	 * @return DateTime
	 */
	public function getCreationTime()
	{
		return $this->creationTime;
	}
	
	/**
	 * Sets creation time
	 * @param DateTime $time
	 */
	public function setCreationTime(DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new DateTime('now');
		}
		$this->creationTime = $time;
	}
	
	/**
	 * Doesn't store modification time
	 * @return DateTime
	 */
	public function getModificationTime()
	{
		return null;
	}

	/**
	 * Doesn't store modification time
	 * @param DateTime $time
	 */
	public function setModificationTime(DateTime $time = null)
	{
		
	}

	/**
	 * @return integer
	 */
	public function getType()
	{
		return $this->type;
	}
	
	/**
	 * @param integer $type 
	 */
	public function setType($type)
	{
		$this->type = $type;
	}
	
	/**
	 * @return string
	 */
	public function getReferenceId()
	{
		return $this->reference;
	}
	
	/**
	 * @param string $referenceId 
	 */
	public function setReferenceId($referenceId)
	{
		$this->reference = $referenceId;
	}
}