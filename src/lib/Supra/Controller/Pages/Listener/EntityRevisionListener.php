<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Supra\Controller\Pages\Entity\Abstraction\AuditedEntityInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionClass;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\Controller\Pages\Entity\Abstraction\OwnedEntityInterface;
use Doctrine\ORM\PersistentCollection;
use Supra\Controller\Pages\Event\AuditEvents;


class EntityRevisionListener implements EventSubscriber
{
	
	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;
	
	/**
	 * @var \Doctrine\ORM\UnitOfWork
	 */
	private $uow;
	
	/**
	 * @var array
	 */
	private $visitedEntities = array();
	
	/**
	 * @var boolean
	 */
	private $_pageRestoreState = false;
	

	public function getSubscribedEvents()
	{
		return array(
			Events::onFlush,
			AuditEvents::pagePreRestoreEvent,
			AuditEvents::pagePostRestoreEvent,
		);
	}
	
	/**
	 * Maps `revision` field/column for Draft (affects Public also) schema
	 * for entities that implements AuditedEntity interface
	 * 
	 * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$metadata = $eventArgs->getClassMetadata();
		$class = new ReflectionClass($metadata->name);
		
		if ($class->implementsInterface(AuditedEntityInterface::INTERFACE_NAME)) {
			if ( ! $metadata->hasField('revision')) {
				$metadata->mapField(array(
					'fieldName' => 'revision',
					'type' => 'string',
					'columnName' => 'revision',
					'length' => 40,
				));
			}
		}
	}

	/**
	 * Listen all entity insertions and updates performed by Draft entity manager,
	 * update their revision Id
	 * 
	 * @param OnFlushEventArgs $eventArgs
	 */
	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		// to skip revision changes when restoring data from audit
		if ($this->_pageRestoreState) {
			return;
		}
		
		$this->em = $eventArgs->getEntityManager();
		$this->uow = $this->em->getUnitOfWork();
		
		$this->visitedEntities = array();
		// is it enough with single revision id for inserts and updates?
		$revisionId = $this->_getRevisionId();
		
		foreach ($this->uow->getScheduledEntityInsertions() as $entity) {

			if ( ! ($entity instanceof AuditedEntityInterface)) {
				continue;
			}
			
			$this->_setRevisionId($entity, $revisionId);
				
		}
		
		foreach ($this->uow->getScheduledEntityUpdates() as $entity) {
			
			if ( ! ($entity instanceof AuditedEntityInterface)) {
				continue;
			}

			$changeSet = $this->uow->getEntityChangeSet($entity);
			foreach($changeSet as $fieldName => $fieldValue) {

				// skip some cases:
				//   - when changeset contains only associated collection
				//   - when Localization lock/unlock action is performed (lock column value update)
				if ($fieldValue instanceof PersistentCollection
						|| ($entity instanceof Localization && $fieldName == 'lock')) {
					unset($changeSet[$fieldName]);
				}
			}

			if ( ! empty($changeSet)) {
				$this->_setRevisionId($entity, $revisionId);
			}
		}

	}
	
	/**
	 * Recursively goes througs $entity 'owners' (see getOwner() implementation
	 * for each entity that implements OwnedEntity interface) and sets for all of
	 * them single $newRevisionId
	 * 
	 * @param Entity $entity
	 * @param string $newRevisionId 
	 */
	private function _setRevisionId($entity, $newRevisionId, $nestedCall = false) 
	{
		// helps to avoid useless revision overwriting
		if (in_array(spl_object_hash($entity), $this->visitedEntities)) {
			return;
		}
		
		$oldRevisionId = $entity->getRevisionId();
		$entity->setRevisionId($newRevisionId);
		
		$this->visitedEntities[] = spl_object_hash($entity);
		
		// let know to UoW, that we manually changed entity revision property
		$this->uow->propertyChanged($entity, 'revision', $oldRevisionId, $newRevisionId);
		
		// this will update originalEntityData
		$class = $this->em->getClassMetadata($entity::CN());
		$this->uow->recomputeSingleEntityChangeSet($class, $entity);
				
		if ($nestedCall) {
			// schedule parent entities to update, otherwise they will not be audited by audit listener
			$this->uow->scheduleForUpdate($entity);
		}
		
		// recursively going up to set up revision for entity owners
		if ($entity instanceof OwnedEntityInterface) {
			$parentEntity = $entity->getOwner();
			if ( ! is_null($parentEntity)) {
				$this->_setRevisionId($parentEntity, $newRevisionId, true);
			}
		}
	}
	
	/**
	 * @return string
	 */
	private function _getRevisionId()
	{
		return sha1(uniqid());
	}
	
	public function pagePreRestoreEvent ()
	{
		$this->_pageRestoreState = true;
	}
	
	public function pagePostRestoreEvent ()
	{
		 $this->_pageRestoreState = false;
	}

}