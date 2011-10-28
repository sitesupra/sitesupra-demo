<?php

namespace Supra\Controller\Pages;

use Supra\Search\IndexerQueue;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\IndexerQueueItemStatus;
use Supra\Search\Entity\Abstraction\IndexerQueueItem;

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

}
