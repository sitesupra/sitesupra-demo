<?php

namespace Supra\Search;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Supra\Search\IndexerQueueItemStatus;
use Supra\Controller\Pages\Search\PageLocalizationFindRequest;
use Supra\Controller\Pages\PageController;

abstract class IndexerServiceAbstract {

	/**
	 * System ID to be used for this project.
	 * @var string
	 */
	private $systemId;

	/**
	 * @return string
	 */
	public function getSystemId() {
		if (is_null($this->systemId)) {
			$info = ObjectRepository::getSystemInfo($this);
			$this->systemId = $info->name;
		}

		return $this->systemId;
	}

	abstract public function processItem(\Supra\Search\Entity\Abstraction\IndexerQueueItem $queueItem);

	abstract public function removeFromIndex($uniqueId);

	abstract public function getDocumentCount();

	abstract public function remove($localizationId);
}