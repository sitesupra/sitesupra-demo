<?php

namespace Supra\BannerMachine\Entity;

use Supra\Database;
use \DateTime;
use Supra\BannerMachine\BannerMachineController;

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

	const STATUS_ACTIVE = 1;
	const STATUS_INACTIVE = 0;

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
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $localeId;

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
	 * @Column(type="datetime", nullable=true)
	 * @var DateTime
	 */
	protected $scheduledFrom;

	/**
	 * @Column(type="datetime", nullable=true)
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

	abstract function getExposureModeContent(BannerMachineController $controller);

	abstract function getEditModeContent(BannerMachineController $controller);
	
	/**
	 * @return integer
	 */
	public function getWidth()
	{
		return $this->width;
	}

	/**
	 * @return integer
	 */
	public function getHeight()
	{
		return $this->height;
	}

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

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @return integer
	 */
	public function getTypeId()
	{
		return $this->typeId;
	}

	/**
	 * @return integer
	 */
	public function getPriority()
	{
		return $this->priority;
	}

	/**
	 * @return integer
	 */
	public function getTargetType()
	{
		return $this->targetType;
	}

	/**
	 * @return integer
	 */
	public function getExposureCount()
	{
		return $this->exposureCount;
	}

	/**
	 * @return float
	 */
	public function getCtr()
	{
		return 0.0;
	}

	/**
	 * @return float
	 */
	public function getAverageCtr()
	{
		return 0.0;
	}
	
	/**
	 * @return string
	 */
	public function getInternalTarget()
	{
		return $this->internalTarget;
	}

	/**
	 * @return string
	 */
	public function getExternalTarget()
	{
		return $this->externalTarget;
	}

	/**
	 * @return string
	 */
	public function getLocaleId()
	{
		return $this->localeId;
	}

	/**
	 * @param string $localeId 
	 */
	public function setLocaleId($localeId)
	{
		$this->localeId = $localeId;
	}

	/**
	 * @return boolean
	 */
	public function hasTarget()
	{
		$targetType = $this->getTargetType();
		
		$internalTarget = $this->getInternalTarget();
		$externalTarget = $this->getExternalTarget();
		
		return
				($targetType == self::TARGET_TYPE_INTERNAL && ! empty($internalTarget) ) ||
				($targetType == self::TARGET_TYPE_EXTERNAL && ! empty($externalTarget) );
	}

	abstract public function getExternalPath();
}
