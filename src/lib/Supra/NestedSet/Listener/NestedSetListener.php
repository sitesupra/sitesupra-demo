<?php

namespace Supra\NestedSet\Listener;

use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityManager;
use Supra\NestedSet\Node\EntityNodeInterface;
use Supra\NestedSet\Node\DoctrineNode;
use Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * Attaches nested set node to the tree element
 */
class NestedSetListener
{
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
			
			// Initialize the doctrine nested set node
			$doctrineNode = new DoctrineNode($repository);
			$entity->setNestedSetNode($doctrineNode);
			$doctrineNode->belongsTo($entity);
		}
	}
}
