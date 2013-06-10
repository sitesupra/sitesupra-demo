<?php

namespace Supra\User\Entity;

use Supra\Database;
use Supra\Authorization\AuthorizedEntityInterface;
use Supra\Authorization\AuthorizationProvider;
use Supra\Database\Doctrine\Listener\Timestampable;
use \DateTime;

/**
 * @Entity
 * @Table(indexes={
 * 		@index(name="userId_idx", columns={"userId"}),
 * 		@index(name="isRead_idx", columns={"isRead"}),
 * 		@index(name="isVisible_idx", columns={"isVisible"})
 * })
 * @HasLifecycleCallbacks
 */
class UserSiteNotification extends Database\Entity
{
    
	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $userId;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $siteId;
    
	/**
	 * @Column(type="boolean", nullable=true)
	 * @var boolean
	 */
	protected $isRead = false;

	/**
	 * @Column(type="boolean", nullable=true)
	 * @var boolean
	 */
	protected $isVisible = true;

	/**
	 * @Column(type="integer", nullable=true)
	 * @var integer
	 */
	protected $type;

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $message;

	/**
	 * @Column(type="datetime", nullable=true)
	 * @var DateTime
	 */
	protected $creationTime;

	/**
	 * @Column(type="datetime", nullable=true)
	 * @var DateTime
	 */
	protected $modificationTime;
    
    
	/**
	 * @return string
	 */
	public function getSiteId()
	{
		return $this->siteId;
	}

	/**
	 * @param string $userId 
	 */
	public function setSiteId($siteId)
	{
		$this->siteId = $siteId;
	}
    
	/**
	 * @return string
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @param string $userId 
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;
	}
    
	/**
	 * @return boolean
	 */
	public function getIsVisible()
	{
		return $this->isVisible;
	}

	/**
	 * @param boolean $isVisible 
	 */
	public function setIsVisible($isVisible)
	{
		$this->isVisible = $isVisible;
	}

	public function getIsRead()
	{
		return $this->isRead;
	}

	public function setIsRead($isRead)
	{
		$this->isRead = $isRead;
	}
    
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
		return $this->modificationTime;
	}

	public function getType()
	{
		return $this->type;
	}

	public function setType($type)
	{
		$this->type = $type;
	}
    
	public function getMessage()
	{
		return $this->message;
	}

	public function setMessage($message)
	{
		$this->message = $message;
	}
    
	/**
	 * @preUpdate
	 * @prePersist
	 */
	public function autoModificationTime()
	{
		$this->modificationTime = new DateTime('now');
	}

	/**
	 * @prePersist
	 */
	public function autoCreationTime()
	{
		$this->creationTime = new DateTime('now');
	}
    
}