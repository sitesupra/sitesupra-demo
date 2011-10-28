<?php

namespace Supra\Cms;

use Supra\Controller\Pages\Event\PagePublishEventArgs;
use Supra\ObjectRepository\ObjectRepository;

class CmsPageLocalizationIndexerQueueListener
{

	public function postPagePublish(PagePublishEventArgs $eventArgs)
	{
		$indexerQueue = ObjectRepository::getIndexerQueue($eventArgs->pageLocalization);
		$indexerQueue->add($eventArgs->pageLocalization);
	}

}
