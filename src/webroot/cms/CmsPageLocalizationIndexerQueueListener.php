<?php

namespace Supra\Cms;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Search\PageLocalizationIndexerQueue;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Search\Solarium\Configuration;
use Supra\Controller\Pages\Event;


class CmsPageLocalizationIndexerQueueListener implements \Doctrine\Common\EventSubscriber
{

	public function getSubscribedEvents() 
	{
		return array(
			Event\PageCmsEvents::pagePostPublish,
			Event\PageCmsEvents::pagePostRemove,
		);
	}

	/**
	 * Will add published pages into search indexer queue
	 */
	public function pagePostPublish(Event\PageCmsEventArgs $eventArgs)
	{
		if ( ! ObjectRepository::isSolariumConfigured($this)) {
			\Log::debug(Configuration::FAILED_TO_GET_CLIENT_MESSAGE);
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
	 */
	public function pagePostRemove(Event\PageCmsEventArgs $eventArgs)
	{
		if ( ! ObjectRepository::isSolariumConfigured($this)) {
			\Log::debug(Configuration::FAILED_TO_GET_CLIENT_MESSAGE);
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
