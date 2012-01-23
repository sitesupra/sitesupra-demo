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

class CmsPageLocalizationIndexerQueueListener
{

	/**
	 * Will add published pages into search indexer queue
	 * @param CmsPagePublishEventArgs $eventArgs
	 */
	public function postPagePublish(CmsPagePublishEventArgs $eventArgs)
	{
		$localization = $eventArgs->localization;

		// Index only pages, not templates
		if ($localization instanceof PageLocalization) {
			$indexerQueue = new PageLocalizationIndexerQueue(PageController::SCHEMA_PUBLIC);
			$indexerQueue->add($eventArgs->localization);
		}
	}

	/**
	 * Will remove indexed pages from search indexer 
	 * @param CmsPageDeleteEventArgs $eventArgs
	 */
	public function postPageDelete(CmsPageDeleteEventArgs $eventArgs)
	{
		$localization = $eventArgs->localization;

		// We care only about pages, not templates.
		if ($localization instanceof PageLocalization) {

			$findRequest = new PageLocalizationFindRequest();

			$findRequest->setSchemaName(PageController::SCHEMA_PUBLIC);
			$findRequest->setPageLocalizationId($localization->getId());

			$searchService = new SearchService();  

			$results = $searchService->processRequest($findRequest);

			foreach ($results as $result) {
				
				if ($result->pageLocalizationId == $localization->getId()) {

					$indexerService = new IndexerService();

					$indexerService->removeFromIndex($result->uniqueId);
				}
			}
		}
	}

}
