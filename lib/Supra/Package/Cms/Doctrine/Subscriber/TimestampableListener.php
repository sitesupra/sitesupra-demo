<?php

namespace Supra\Package\Cms\Doctrine\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Supra\Package\Cms\Entity\Abstraction\TimestampableInterface;

class TimestampableListener implements EventSubscriber
{
	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(Events::onFlush, Events::prePersist);
	}

	/**
	 * Sets modification time on updates
	 * @param OnFlushEventArgs $eventArgs
	 */
	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		$em = $eventArgs->getEntityManager();
		$uow = $em->getUnitOfWork();

		foreach ($uow->getScheduledEntityUpdates() as $entity) {
			if ($entity instanceof TimestampableInterface) {
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

		if ($entity instanceof TimestampableInterface) {
			$now = new \DateTime();
			$entity->setModificationTime($now);
			$entity->setCreationTime($now);
		}
	}
}
