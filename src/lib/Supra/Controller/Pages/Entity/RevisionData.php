<?php

namespace Supra\Controller\Pages\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Supra\Controller\Pages\Exception;

/**
 * Revision data class
 * @Entity
 * @HasLifecycleCallbacks
 */
class RevisionData extends Abstraction\Entity
{
	/**
	 * @Column(type="datetime", nullable=true, name="created_at")
	 * @var DateTime
	 */
	protected $created;
	
	/**
	 * @Column(type="sha1")
	 * @var string
	 */
	protected $user;
	
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
}