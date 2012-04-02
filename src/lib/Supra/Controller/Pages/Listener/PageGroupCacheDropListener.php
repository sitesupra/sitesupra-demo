<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\Common\EventSubscriber;
use Supra\Controller\Pages\Event\CmsPageEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Supra\Controller\Pages\Event\CmsPagePublishEventArgs;
use Supra\Controller\Pages\Event\CmsPageDeleteEventArgs;
use Supra\Cache\CacheGroupManager;
use Supra\Controller\Pages\PageController;
use Supra\NestedSet\Event\NestedSetEventArgs;
use Supra\NestedSet\Event\NestedSetEvents;
use Supra\Controller\Pages\Entity\Abstraction\AbstractPage;

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
		return array(NestedSetEvents::nestedSetPostMove, CmsPageEventArgs::postPagePublish, CmsPageEventArgs::postPageDelete);
	}
	
	public function nestedSetPostMove(NestedSetEventArgs $eventArgs)
	{
		if ($eventArgs->getEntity() instanceof AbstractPage) {
			$this->dropCache();
		}
	}
	
	public function postPagePublish(CmsPagePublishEventArgs $eventArgs)
	{
		$this->dropCache();
	}
	
	public function postPageDelete(CmsPageDeleteEventArgs $eventArgs)
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
