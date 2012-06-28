<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Supra\Controller\Pages\Entity\Abstraction\AuditedEntityInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionClass;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * Maps revision field as the entity column if class implements the audited entity interface
 */
class EntityRevisionFieldMapperListener implements EventSubscriber
{
	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(Events::loadClassMetadata);
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
		
		if ( ! class_exists($metadata->name)) {
			return;
		}
		
		$class = new ReflectionClass($metadata->name);
		
		if ($class->implementsInterface(AuditedEntityInterface::CN)) {
			if ( ! $metadata->hasField('revision')) {
				$metadata->mapField(array(
					'fieldName' => 'revision',
					'type' => 'string',
					'columnName' => 'revision',
					'length' => 20,
				));
			}
		}
	}

}
