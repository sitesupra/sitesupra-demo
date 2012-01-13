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
	 * @Column(type="supraId20")
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
		$document = new IndexedDocument(DummyItem::CN(), $this->actualObject->id . '-' . $this->actualObject->revision);
		
		$document->uniqueId = $this->actualObject->id . '-' . $this->actualObject->revision;
		$document->class = DummyItem::CN();
		
		$document->revisionId = $this->actualObject->revision;
		
		$document->text_general = $this->actualObject->text;
		
		return array($document);
	}

}
