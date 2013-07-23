<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\Common\EventSubscriber;
use Supra\Cache\CacheGroupManager;
use Supra\Controller\Pages\PageController;
use Supra\NestedSet\Event\NestedSetEventArgs;
use Supra\NestedSet\Event\NestedSetEvents;
use Supra\Controller\Pages\Entity\Abstraction\AbstractPage;
use Supra\Controller\Pages\Event;

/**
 * Drops page group cache when page/template is moved or published or deteled
 */
class PageGroupCacheDropListener implements EventSubscriber
{
	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(
			NestedSetEvents::nestedSetPostMove, 
			Event\PageCmsEvents::pagePostPublish,
			Event\PageCmsEvents::pagePostRemove,
		);
	}
	
	public function nestedSetPostMove(NestedSetEventArgs $eventArgs)
	{
		if ($eventArgs->getEntity() instanceof AbstractPage) {
			$this->dropCache();
		}
	}
	
	public function pagePostPublish(Event\PageCmsEventArgs $eventArgs)
	{
		$this->dropCache();
	}
	
	public function pagePostRemove(Event\PageCmsEventArgs $eventArgs)
	{
		$this->dropCache();
	}
	
	/**
	 * Main function
	 */
	private function dropCache()
	{
		$cache = new CacheGroupManager();
		$cache->resetRevision(PageController::CACHE_GROUP_NAME);
	}
}
