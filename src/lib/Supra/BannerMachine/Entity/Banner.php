<?php

namespace Supra\BannerMachine\Entity;

use Supra\Database;
use \DateTime;

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"image" = "ImageBanner", "flash" = "FlashBanner"})
 */
abstract class Banner extends Database\Entity
{
	const TARGET_TYPE_INTERNAL = 1;
	const TARGET_TYPE_EXTERNAL = 2;

	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $title;

	/**
	 * @Column(type="integer", nullable=false)
	 * @var integer
	 */
	protected $targetType;

	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $internalTarget;

	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $externalTarget;

	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $scheduledFrom;

	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $scheduledTill;

	/**
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $status;

	/**
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $exposureCount = 0;

	/**
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $clickCount = 0;

	/**
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $priority;

	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $creationTime;

	/**
	 * @Column(type="datetime")
	 * @var DateTime
	 */
	protected $modificationTime;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $typeId;

	abstract function getContent();

	public function getWidth()
	{
		return $this->width;
	}

	public function getHeight()
	{
		return $this->height;
	}

	abstract function validate();

	/**
	 * @prePersist
	 */
	public function autoCretionAndModificationTime()
	{
		$this->creationTime = new DateTime('now');
		$this->modificationTime = new DateTime('now');
	}

	/**
	 * @preUpdate
	 */
	public function autoModificationTime()
	{
		$this->modificationTime = new DateTime('now');
	}

	/**
	 * @return integer
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @param integer $status 
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}

	public function setPriority($priority)
	{
		$this->priority = $priority;
	}

	public function setTitle($title)
	{
		$this->title = $title;
	}

	public function setTypeId($typeId)
	{
		$this->typeId = $typeId;
	}

	public function setScheduledFrom($scheduledFrom)
	{
		$this->scheduledFrom = $scheduledFrom;
	}

	public function setScheduledTill($scheduledTill)
	{
		$this->scheduledTill = $scheduledTill;
	}

	/**
	 * @return DateTime
	 */
	public function getScheduledFrom()
	{
		return $this->scheduledFrom;
	}

	/**
	 * @return DateTime
	 */
	public function getScheduledTill()
	{
		return $this->scheduledTill;
	}

	/**
	 * @param string $href 
	 */
	public function setExternalTarget($href)
	{
		$this->externalTarget = $href;
		$this->targetType = self::TARGET_TYPE_EXTERNAL;
	}

	/**
	 * @param string $pageId 
	 */
	public function setInternalTarget($pageId)
	{
		$this->internalTarget = $pageId;
		$this->targetType = self::TARGET_TYPE_INTERNAL;
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function getTypeId()
	{
		return $this->typeId;
	}

	public function getPriority()
	{
		return $this->priority;
	}

	public function getTargetType()
	{
		return $this->targetType;
	}

	public function getExposureCount()
	{
		return $this->exposureCount;
	}

	public function getCtr()
	{
		return 0;
	}

	public function getAverageCtr()
	{
		return 0;
	}

	public function getInternalTarget()
	{
		return $this->internalTarget;
	}

	public function getExternalTarget()
	{
		return $this->externalTarget;
	}

}
