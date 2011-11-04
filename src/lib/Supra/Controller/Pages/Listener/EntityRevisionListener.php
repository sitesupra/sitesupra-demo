<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Supra\Controller\Pages\Entity\Abstraction\AuditedEntity;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Supra\Controller\Pages\Entity\RevisionData;
use ReflectionClass;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;


class EntityRevisionListener implements EventSubscriber
{
	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $em;
	
	public function getSubscribedEvents()
	{
		return array(
			Events::onFlush,
			//Events::postUpdate,
			//Events::postPersist,
			
			//Events::preUpdate,
			//Events::prePersist,
		);
	}

	public function postPersist(LifecycleEventArgs $eventArgs)
	{
		$entity = $eventArgs->getEntity();
		$metadata = $eventArgs->getEntityManager()
				->getClassMetadata($entity::CN());
		
		//unset($metadata->fieldMappings['revision']['inherited']);
		/*
		$entity = $eventArgs->getEntity();
		if ( ! ($entity instanceof AuditedEntity)) {
			return;
		}
		
		$class = $this->auditEm->getClassMetadata($entity::CN());
		
		$this->saveRevisionEntityData($class, $this->uow->getOriginalEntityData($entity), self::REVISION_TYPE_INSERT);
		*/
	 }


	public function postUpdate(LifecycleEventArgs $eventArgs)
	{
		/*
		$entity = $eventArgs->getEntity();
		if ( ! ($entity instanceof AuditedEntity)) {
			return;
		}
		
		$class = $this->auditEm->getClassMetadata($entity::CN());

		$entityData = array_merge($this->uow->getOriginalEntityData($entity), $this->uow->getEntityIdentifier($entity));
		$this->saveRevisionEntityData($class, $entityData, self::REVISION_TYPE_UPDATE);
		*/
	}

	
	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		$this->em = $eventArgs->getEntityManager();
		
		$uow = $this->em->getUnitOfWork();
		
		foreach ($uow->getScheduledEntityInsertions() as $entity) {
			$revisionId = $this->_getRevisionId();
			$entity->setRevisionId($revisionId);
			
			$class = $this->em->getClassMetadata($entity::CN());
			
			//$uow->computeChangeSet($class, $entity);
			$uow->recomputeSingleEntityChangeSet($class, $entity);
		}
		
		foreach ($uow->getScheduledEntityUpdates() as $entity) {
			$revisionId = $this->_getRevisionId();
			$entity->setRevisionId($revisionId);
		}
		
	}
	
	
	
	public function prePersist(LifecycleEventArgs $eventArgs)
	{
		return;
		$this->em = $eventArgs->getEntityManager();
		$entity = $eventArgs->getEntity();
		if ( ! ($entity instanceof AuditedEntity)) {
			return;
		}

		$revisionId = $this->_getRevisionId();
		$entity->setRevisionId($revisionId);
		
		//$uow = $this->em->getUnitOfWork();
		//$uow->recomputeSingleEntityChangeSet($entity::CN(), $entity);
	}
	
	public function preUpdate(LifecycleEventArgs $eventArgs)
	{
		return;
		$this->em = $eventArgs->getEntityManager();
		$entity = $eventArgs->getEntity();
		if ( ! ($entity instanceof AuditedEntity)) {
			return;
		}
		
		$uow = $this->em->getUnitOfWork();
		$changeSet = $uow->getEntityChangeSet($entity);
		if (empty($changeSet) || (count($changeSet) == 1 && isset($changeSet['revision']))) {
			return;
		}
		
		
		$revisionId = $this->_getRevisionId();
		$entity->setRevisionId($revisionId);
		
		//$uow = $this->em->getUnitOfWork();
		//$uow->recomputeSingleEntityChangeSet($entity::CN(), $entity);
	}
	
	private function _getRevisionId()
	{
		return md5(uniqid());
		$revisionData = new RevisionData();
		// FIXME: assign real user
		// possible solution:
		//		- create custom event, fire it somwhere in page manager controller,
		//		  pass user id and store it here, in private property
		$revisionData->setUser('i-have-no-user-fix-me');
		
		$this->em->persist($revisionData);
		$revisionId = $revisionData->getId();
		
		return $revisionId;
	}
	
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$metadata = $eventArgs->getClassMetadata();
		$class = new ReflectionClass($metadata->name);
		
		if ($class->implementsInterface(AuditedEntity::INTERFACE_NAME)) {
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

}