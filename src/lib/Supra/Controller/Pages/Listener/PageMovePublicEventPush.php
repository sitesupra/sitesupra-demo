<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\Common\EventSubscriber;
use Supra\NestedSet\Event\NestedSetEventArgs;
use Supra\NestedSet\Event\NestedSetEvents;
use Supra\Controller\Pages\Entity\Abstraction\AbstractPage;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\PageController;

/**
 * This listener pushes the page move events from draft to public schema 
 * subscribers as well, because they both share tables.
 */
class PageMovePublicEventPush implements EventSubscriber
{
	public function getSubscribedEvents()
	{
		return array(NestedSetEvents::nestedSetPostMove);
	}
	
	public function nestedSetPostMove(NestedSetEventArgs $eventArgs)
	{
		$entity = $eventArgs->getEntity();
		
		if ($entity instanceof AbstractPage) {
			$publicEntityManager = ObjectRepository::getEntityManager(PageController::SCHEMA_PUBLIC);
			$currentEntityManager = $eventArgs->getEntityManager();
			
			if ($publicEntityManager !== $currentEntityManager) {
				$publicEntity = $publicEntityManager->find(AbstractPage::CN(), $entity->getId());
				$publicEventArgs = new NestedSetEventArgs($publicEntity, $publicEntityManager);
				$publicEntityManager->getEventManager()
						->dispatchEvent(NestedSetEvents::nestedSetPostMove, $publicEventArgs);
			}
		}
	}
}
