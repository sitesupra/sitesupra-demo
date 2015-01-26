<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

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
