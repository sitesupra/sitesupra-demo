<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\Common\EventSubscriber;
use Supra\Cms\CmsController;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Supra\Controller\Pages\Event\CmsPagePublishEventArgs;
use Supra\Controller\Pages\Event\CmsPageDeleteEventArgs;
use Supra\Cache\CacheGroupManager;
use Supra\Controller\Pages\PageController;

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
		return array(PagePathGenerator::postPageMove, CmsController::EVENT_POST_PAGE_PUBLISH, CmsController::EVENT_POST_PAGE_DELETE);
	}
	
	public function postPageMove(LifecycleEventArgs $eventArgs)
	{
		$this->dropCache();
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
