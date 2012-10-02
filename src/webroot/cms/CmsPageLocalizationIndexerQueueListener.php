<?php

namespace Supra\Cms;

use Supra\Controller\Pages\Event\CmsPagePublishEventArgs;
use Supra\Controller\Pages\Event\CmsPageDeleteEventArgs;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\PageLocalizationIndexerQueueItem;
use Supra\Controller\Pages\Search\PageLocalizationIndexerQueue;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Search\IndexerService;
use Supra\Search\SearchService;
use Supra\Controller\Pages\Search\PageLocalizationFindRequest;
use Supra\Search\Solarium\Configuration;

class CmsPageLocalizationIndexerQueueListener
{

	/**
	 * Will add published pages into search indexer queue
	 * @param CmsPagePublishEventArgs $eventArgs
	 */
	public function postPagePublish(CmsPagePublishEventArgs $eventArgs)
	{
		try {
			ObjectRepository::getSolariumClient($this);
		} catch (\Exception $e) {
			$message = Configuration::FAILED_TO_GET_CLIENT_MESSAGE;
			\Log::debug($message . PHP_EOL . $e->__toString());
			return;
		}

		$localization = $eventArgs->localization;

		// Index only pages, not templates
		if ($localization instanceof PageLocalization) {
			$indexerQueue = new PageLocalizationIndexerQueue(PageController::SCHEMA_PUBLIC);
			$indexerQueue->add($localization);
		}
	}

	/**
	 * Will remove indexed pages from search indexer 
	 * @param CmsPageDeleteEventArgs $eventArgs
	 */
	public function postPageDelete(CmsPageDeleteEventArgs $eventArgs)
	{
		try {
			ObjectRepository::getSolariumClient($this);
		} catch (\Exception $e) {
			$message = Configuration::FAILED_TO_GET_CLIENT_MESSAGE;
			\Log::debug($message . PHP_EOL . $e->__toString());
			return;
		}

		$localization = $eventArgs->localization;

		// Index only pages, not templates
		if ($localization instanceof PageLocalization) {
			$indexerQueue = new PageLocalizationIndexerQueue(PageController::SCHEMA_PUBLIC);
			$indexerQueue->addRemoval($localization);
		}
	}

}
