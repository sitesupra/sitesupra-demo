<?php

namespace Supra\Search;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\Entity\Abstraction\IndexerQueueItem;

abstract class IndexerAbstract {

	/**
	 * System ID to be used for this project.
	 * @var string
	 */
	private $systemId;

	/**
	 * @return string
	 */
	public function getSystemId()
	{
		if ($this->systemId === null) {
			$this->systemId = ObjectRepository::getSystemInfo($this)->name;
		}

		return $this->systemId;
	}

	abstract public function processItem(IndexerQueueItem $queueItem);

	abstract public function removeFromIndex($uniqueId);
	
	abstract public function removeAllFromIndex();

	abstract public function getDocumentCount();

	abstract public function remove($localizationId);
	
	
}