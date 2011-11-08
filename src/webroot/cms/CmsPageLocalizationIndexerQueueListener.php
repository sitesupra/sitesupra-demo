<?php

namespace Supra\Cms;

use Supra\Controller\Pages\Event\PagePublishEventArgs;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\PageLocalizationIndexerQueueItem;
use Supra\Controller\Pages\Search\PageLocalizationIndexerQueue;
use Supra\Controller\Pages\PageController;

class CmsPageLocalizationIndexerQueueListener
{

	public function postPagePublish(PagePublishEventArgs $eventArgs)
	{
		$indexerQueue = new PageLocalizationIndexerQueue(PageController::SCHEMA_PUBLIC);
		
		$indexerQueue->add($eventArgs->pageLocalization);
	}

}
