<?php

namespace Supra\Cms;

use Supra\Controller\Pages\Event\PagePublishEventArgs;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\PageLocalizationIndexerQueueItem;
use Supra\Controller\Pages\PageController;

class CmsPageLocalizationIndexerQueueListener
{

	public function postPagePublish(PagePublishEventArgs $eventArgs)
	{
		$indexerQueue = ObjectRepository::getIndexerQueue($eventArgs->pageLocalization);

		$indexerQueueItem = $indexerQueue->add($eventArgs->pageLocalization);

		// We have to set schema name for this queued page localization 
		// indexer queue item to PUBLIC and then store item again.
			$indexerQueueItem->setSchemaName(PageController::SCHEMA_PUBLIC);
		if ($indexerQueueItem instanceof PageLocalizationIndexerQueueItem) {
		}

		$indexerQueue->store($indexerQueueItem);
	}

}
