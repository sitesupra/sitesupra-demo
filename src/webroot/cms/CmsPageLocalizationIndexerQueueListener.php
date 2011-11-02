<?php

namespace Supra\Cms;

use Supra\Controller\Pages\Event\PagePublishEventArgs;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\PageLocalizationIndexerQueueItem;
use Supra\Controller\Pages\PageLocalizationIndexerQueue;
use Supra\Controller\Pages\PageController;

class CmsPageLocalizationIndexerQueueListener
{

	public function postPagePublish(PagePublishEventArgs $eventArgs)
	{
		$indexerQueue = new PageLocalizationIndexerQueue();

		$indexerQueueItem = $indexerQueue->add($eventArgs->pageLocalization);

		// We have to set schema name for this queued page localization 
		// indexer queue item to PUBLIC and then store item again.
		$indexerQueueItem->setSchemaName(PageController::SCHEMA_PUBLIC);
		$indexerQueue->store($indexerQueueItem);
	}

}
