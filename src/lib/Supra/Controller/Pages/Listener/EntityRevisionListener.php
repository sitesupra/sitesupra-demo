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
use Supra\Controller\Pages\Entity\Abstraction\Localization;


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
		);
	}

	/**
	 * Listen all entity insertions and updates performed by Draft entity manager
	 * and fill provided entities with revision ID
	 * 
	 * @param OnFlushEventArgs $eventArgs
	 */
	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		$this->em = $eventArgs->getEntityManager();
		$uow = $this->em->getUnitOfWork();
		
		foreach ($uow->getScheduledEntityInsertions() as $entity) {
			$revisionId = $this->_getRevisionId();
			$entity->setRevisionId($revisionId);

			$class = $this->em->getClassMetadata($entity::CN());
			$uow->recomputeSingleEntityChangeSet($class, $entity);
		}
		
		foreach ($uow->getScheduledEntityUpdates() as $entity) {
			
			// skip revision updates in cases when page lock/unlock was performed
			// TODO: another, more elegant solution?
			if ($entity instanceof Localization) {
				$changeSet = $this->em
						->getUnitOfWork()
							->getEntityChangeSet($entity);
				
				if (isset($changeSet['lock'])) {
					return;
				}
			}
			
			$revisionId = $this->_getRevisionId();
			$entity->setRevisionId($revisionId);
			
			$class = $this->em->getClassMetadata($entity::CN());
			$uow->recomputeSingleEntityChangeSet($class, $entity);
		}
	}
	
	private function _getRevisionId()
	{
		return sha1(uniqid());
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