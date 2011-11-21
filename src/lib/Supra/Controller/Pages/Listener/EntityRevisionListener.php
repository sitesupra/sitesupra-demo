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
	}
	
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

}