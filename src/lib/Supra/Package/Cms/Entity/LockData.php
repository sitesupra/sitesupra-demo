<?php

namespace Supra\Package\Cms\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Supra\Package\Cms\Entity\Abstraction\Localization;

/**
 * Editing lock.
 * 
 * @Entity
 * @Table(name="lock_data")
 */
class LockData extends Abstraction\Entity implements Abstraction\TimestampableInterface
{
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $userName;

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
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $localizationRevision;

	/**
	 * @param UserInterface $user
	 * @param Localization $localization
	 */
	public function __construct(UserInterface $user, Localization $localization)
	{
		parent::__construct();
		
		$this->userName = $user->getUsername();
		$this->localizationRevision = $localization->getRevision();

		$localization->setLock($this);
	}

	/**
	 * Returns creation time.
	 *
	 * @return \DateTime
	 */
	public function getCreationTime()
	{
		return $this->creationTime;
	}
	
	/**
	 * Sets creation time.
	 * 
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
	 * Returns last modification time.
	 * 
	 * @return \DateTime
	 */
	public function getModificationTime()
	{
		return $this->modificationTime;
	}

	/**
	 * Sets modification time.
	 * 
	 * @param \DateTime $time
	 */
	public function setModificationTime(\DateTime $time = null)
	{
		$this->modificationTime = $time ? $time : new \DateTime();
	}

	/**
	 * Returns username of user who created lock.
	 * 
	 * @return string
	 */
	public function getUserName()
	{
		return $this->userName;
	}

	/**
	 * Returns localization revision on the lock creation moment.
	 *
	 * @return string
	 */
	public function getLocalizationRevision()
	{
		return $this->localizationRevision;
	}
}
