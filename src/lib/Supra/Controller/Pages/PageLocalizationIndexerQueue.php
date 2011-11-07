<?php

namespace Supra\Controller\Pages;

use Supra\Search\IndexerQueue;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\IndexerQueueItemStatus;
use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Entity\PageLocalizationIndexerQueueItem;

class PageLocalizationIndexerQueue extends IndexerQueue
{

	function __construct()
	{
		parent::__construct(Entity\PageLocalizationIndexerQueueItem::CN());
	}

	/**
	 * @param PageLocalization $pageLocalization
	 * @param integer $status
	 * @return IndexerQueueItem
	 */
	public function getOneByObjectAndStatus($pageLocalization, $status)
	{
		$criteria = array(
				'pageLocalizationId' => $pageLocalization->getId(),
				'revisionId' => $pageLocalization->getRevisionId(),
				'status' => $status
		);

		$queueItem = $this->repository->findOneBy($criteria);

		return $queueItem;
	}

	/**
	 *
	 * @param PageLocalization $pageLocalization
	 * @param integer $priority
	 * @param string $schemaName
	 * @return PageLocalizationIndexerQueueItem
	 */
	public function add($pageLocalization, $priority = IndexerQueueItem::DEFAULT_PRIORITY, $schemaName = PageController::SCHEMA_CMS)
	{
		/* @var $queueItem PageLocalizationIndexerQueueItem */
		$queueItem = parent::add($pageLocalization, $priority);

		$queueItem->setSchemaName($schemaName);

		$this->store($queueItem);

		return $queueItem;
	}

}
