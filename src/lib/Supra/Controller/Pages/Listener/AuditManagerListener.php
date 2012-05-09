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
use Supra\Controller\Pages\Entity;
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
			if ( ! is_null($draftTemplate)) {
				$entity->setTemplate($draftTemplate);
			} else {
				$entity->setNullTemplate();
			}
			
			// PageLocalization loaded from Audit schema could contain null path id
			// or id of unexisting path, which will cause EntityNotFoundException
			// if someone will try to get localization path
			// To avoid that, we will load path entity from actual localization
			// 
			// @TODO: generate new path entity using audit localization pathPart and
			// and parents draft pathes
			$id = $entity->getId();
			$draftLocalization = $draftEm->find(PageLocalization::CN(), $id);
			if ( ! is_null($draftLocalization)) {
				$draftPath = $draftLocalization->getPathEntity();
				
				$em->detach($entity);
				$entity->setPathEntity($draftPath);
			} else {
				$entity->resetPath();
			}
		}
		
		else if ($entity instanceof BlockProperty) {
			$entityOriginalData = $em->getUnitOfWork()->getOriginalEntityData($entity);
			
			$block = $entity->getBlock();
			if (is_null($block)) {
				$draftBlock = $draftEm->find(Block::CN(), $entityOriginalData['block_id']);
				if ( ! is_null($draftBlock)) {
					$entity->setBlock($draftBlock);
				}
			}
		}		
	}

}