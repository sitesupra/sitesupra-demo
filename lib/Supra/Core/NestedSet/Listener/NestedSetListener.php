<?php

namespace Supra\Core\NestedSet\Listener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Supra\Core\NestedSet\Node\EntityNodeInterface;
use Supra\Core\NestedSet\Node\DoctrineNode;

/**
 * Attaches nested set node to the tree element
 */
class NestedSetListener implements EventSubscriber
{
	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(Events::prePersist, Events::postLoad, Events::preRemove);
	}
	
	/**
	 * @param LifecycleEventArgs $args 
	 */
	public function postLoad(LifecycleEventArgs $args)
	{
		$this->createNestedSetNode($args);
	}

	/**
	 * @param LifecycleEventArgs $args
	 */
	public function prePersist(LifecycleEventArgs $args)
	{
		$this->createNestedSetNode($args);
	}
	
	/**
	 * @param LifecycleEventArgs $args
	 */
	public function preRemove(LifecycleEventArgs $args)
	{
		$entity = $args->getEntity();
		
		if ($entity instanceof EntityNodeInterface) {
			$entity->delete();
		}
	}
	
	/**
	 * Creates the Doctrine nested set node
	 * @param EntityManager $em
	 * @param object $entity
	 */
	private function createNestedSetNode(LifecycleEventArgs $args)
	{
		$entity = $args->getEntity();

		if ($entity instanceof EntityNodeInterface) {
			// Read entity data from the event arguments
			$em = $args->getEntityManager();
			
			// Find the repository
			$entityName = $entity->getNestedSetRepositoryClassName();
			$repository = $em->getRepository($entityName);
			
			// In case of refresh it already exists..
			$doctrineNode = $entity->getNestedSetNode();
			
			// .. create new if doesn't
			if ($doctrineNode === null) {
				// Initialize the doctrine nested set node
				$doctrineNode = new DoctrineNode($repository);
				$entity->setNestedSetNode($doctrineNode);
			}

			$doctrineNode->belongsTo($entity);
		}
	}
}
