<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Supra\Controller\Pages\Exception\LogicException;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\Template;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Controller\Pages\Entity\Abstraction\Block;
/**
 * Makes sure no manual changes are performed
 */
class AuditManagerListener implements EventSubscriber
{

	public function getSubscribedEvents()
	{
		return array(
			Events::onFlush,
			Events::postLoad,
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
	
	/**
	 * Manually pre-load missing associated entities from draft schema
	 * @TODO: avoid using of hardcoded values
	 */
	public function postLoad(LifecycleEventArgs $eventArgs)
	{
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		$draftEm = ObjectRepository::getEntityManager('#cms');
		
		if ($entity instanceof PageLocalization) {
			$entityOriginalData = $em->getUnitOfWork()->getOriginalEntityData($entity);
			
			$draftTemplate = $draftEm->find(Template::CN(), $entityOriginalData['template_id']);
			$entity->setTemplate($draftTemplate);
		}
		
		else if ($entity instanceof BlockProperty) {
			$entityOriginalData = $em->getUnitOfWork()->getOriginalEntityData($entity);
			
			$block = $entity->getBlock();
			if (is_null($block)) {
				$draftBlock = $draftEm->find(Block::CN(), $entityOriginalData['block_id']);
				$entity->setBlock($draftBlock);
			}
		}		
	}

}