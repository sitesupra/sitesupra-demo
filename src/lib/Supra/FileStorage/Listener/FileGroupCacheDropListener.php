<?php

namespace Supra\FileStorage\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Supra\FileStorage\Entity\Abstraction\Entity;
use Supra\FileStorage\FileStorage;
use Supra\Cache\CacheGroupManager;

/**
 * Drops file cache on changes in the database
 */
class FileGroupCacheDropListener implements EventSubscriber
{
	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(Events::onFlush);
	}

	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		$uow = $eventArgs->getEntityManager()
				->getUnitOfWork();

		$scheduledInsertions = $uow->getScheduledEntityInsertions();
		$scheduledUpdates = $uow->getScheduledEntityUpdates();

		foreach (array_merge($scheduledInsertions, $scheduledUpdates) as $entity) {
			if ($entity instanceof Entity) {
				$cache = new CacheGroupManager();
				$cache->resetRevision(FileStorage::CACHE_GROUP_NAME);

				return;
			}
		}
	}
}
