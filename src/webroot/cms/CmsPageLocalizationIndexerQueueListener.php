<?php

namespace Supra\Cms;

use Supra\Controller\Pages\Event\PagePublishEventArgs;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\PageLocalizationIndexerQueueItem;
use Supra\Controller\Pages\Search\PageLocalizationIndexerQueue;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Entity\PageLocalization;

class CmsPageLocalizationIndexerQueueListener
{
	/**
	 * Will add published pages into search indexer queue
	 * @param PagePublishEventArgs $eventArgs
	 */
	public function postPagePublish(PagePublishEventArgs $eventArgs)
	{
		$localization = $eventArgs->localization;
		
		// Index only pages, not templates
		if ($localization instanceof PageLocalization) {
			$indexerQueue = new PageLocalizationIndexerQueue(PageController::SCHEMA_PUBLIC);
			$indexerQueue->add($eventArgs->localization);
		}
	}

}
