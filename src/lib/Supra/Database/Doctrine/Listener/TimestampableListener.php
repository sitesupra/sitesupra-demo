<?php

namespace Supra\Database\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use DateTime;

/**
 * Timestampable listener
 */
class TimestampableListener
{
	/**
	 * Sets modification time on updates
	 * @param OnFlushEventArgs $eventArgs
	 */
	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		$em = $eventArgs->getEntityManager();
		$uow = $em->getUnitOfWork();
		
		foreach ($uow->getScheduledEntityUpdates() as $entity) {
			if ($entity instanceof Timestampable) {
				$entity->setModificationTime();
				$className = get_class($entity);
				$class = $em->getClassMetadata($className);
				$uow->recomputeSingleEntityChangeSet($class, $entity);
			}
		}
	}
	
	/**
	 * Sets both – creation time and modification time
	 * @param LifecycleEventArgs $eventArgs
	 */
	public function prePersist(LifecycleEventArgs $eventArgs)
	{
		$entity = $eventArgs->getEntity();
		
		if ($entity instanceof Timestampable) {
			$now = new DateTime();
			$entity->setModificationTime($now);
			$entity->setCreationTime($now);
		}
	}
}
