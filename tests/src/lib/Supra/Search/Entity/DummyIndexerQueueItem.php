<?php

namespace Supra\Tests\Search\Entity;

use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Supra\Search\IndexerService;
use Supra\Tests\Search\DummyItem;

/**
 * @Entity
  */
class DummyIndexerQueueItem extends IndexerQueueItem
{

 /**
	 * @Column(type="sha1")
	 * @var string
	 */
	protected $dummyId;

	/**
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $dummyRevision;

	/**
	 * @var DummyItem
	 */
	private $object;

	function __construct()
	{
		parent::__construct();
	}

	function setDummyItem(DummyItem $dummyItem)
	{

		$this->object = $dummyItem;

		$this->dummyId = $dummyItem->id;
		$this->dummyRevision = $dummyItem->revision;
	}

	public function getData()
	{
		return true;
	}

}
