<?php

namespace Supra\Tests\Search\Entity;

use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Supra\Search\IndexerService;
use Supra\Tests\Search\DummyItem;
use Supra\Search\IndexedDocument;
use \DateTime;

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
	private $actualObject;
		
	function __construct(DummyItem $dummyItem) 
	{
		parent::__construct();
		
		$this->actualObject = $dummyItem;
		$this->dummyId = $dummyItem->id;
		$this->dummyRevision = $dummyItem->revision;
	}

	/**
	 *
	 * @return array of IndexedDocument
	 */
	public function getIndexedDocuments()
	{
		$document = new IndexedDocument();
		
		$document->uniqueId = $this->actualObject->id . '-' . $this->actualObject->revision;
		$document->class = 'Dummyyyyyyyyyyyy';
		
		$document->revisionId = $this->actualObject->revision;
		
		$document->text = $this->actualObject->text;
		
		return array($document);
	}

}
