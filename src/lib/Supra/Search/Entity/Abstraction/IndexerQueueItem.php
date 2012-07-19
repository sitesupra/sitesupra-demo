<?php

namespace Supra\Search\Entity\Abstraction;

use DateTime;
use Supra\Database\Doctrine\Listener\Timestampable;
use Supra\Search\IndexerQueueItemStatus;
use Supra\Search\IndexedDocument;

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DetachedDiscriminators
  */
abstract class IndexerQueueItem extends Entity implements Timestampable
{
	const DEFAULT_PRIORITY = 50;

	/**
	 * @Column(type="integer", nullable=false)
	 * @var integer
	 */
	protected $status;

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
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $priority;

	function __construct()
	{
		parent::__construct();

		$this->priority = self::DEFAULT_PRIORITY;
		$this->status = IndexerQueueItemStatus::FRESH;
	}

	/**
	 * Returns priority of queue item.
	 * @return integer
	 */
	public function getPriority()
	{
		return $this->priority;
	}

	/**
	 * Sets priority for queue item.
	 * @param integer $priority 
	 */
	public function setPriority($priority)
	{
		$this->priority = $priority;
	}

	/**
	 * Returns creation time.
	 * @return DateTime
	 */
	public function getCreationTime()
	{
		return $this->creationTime;
	}

	/**
	 * Sets creation time.
	 * @param DateTime $time
	 */
	public function setCreationTime(DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new DateTime();
		}
		$this->creationTime = $time;
	}

	/**
	 * Returns last modification time.
	 * @return DateTime
	 */
	public function getModificationTime()
	{
		return $this->modificationTime;
	}

	/**
	 * Sets modification time.
	 * @param DateTime $time
	 */
	public function setModificationTime(DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new DateTime();
		}
		$this->modificationTime = $time;
	}

	/**
	 * Sets status of this indexer queue item. Use constants from IndexerQueueItemStatus class.
	 * @param integer $newStatus 
	 */
	public function setStatus($newStatus)
	{
		$this->status = IndexerQueueItemStatus::validate($newStatus);
	}

	/**
	 * Returns status of this queue item.
	 * @return integer
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * Returns document to be added to Solr.
	 * @return IndexedDocument
	 */
	abstract public function getIndexedDocuments();
	
	
	abstract public function writeIndexedDocuments($documentWriter);
}
