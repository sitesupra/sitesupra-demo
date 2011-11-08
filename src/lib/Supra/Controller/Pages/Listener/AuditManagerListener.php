<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Supra\Controller\Pages\Exception\LogicException;

/**
 * Makes sure no manual changes are performed
 */
class AuditManagerListener implements EventSubscriber
{

	public function getSubscribedEvents()
	{
		return array(
			Events::onFlush,
		);
	}

	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		$uow = $eventArgs->getEntityManager()
				->getUnitOfWork();
		
		$scheduledInsertions = $uow->getScheduledEntityInsertions();
		$scheduledUpdates = $uow->getScheduledEntityUpdates();
		
		if (count($scheduledInsertions) > 0 || count($scheduledUpdates) > 0) {
			throw new LogicException('Audit EntityManager is read only. Only deletions are allowed');
		}
	}

}