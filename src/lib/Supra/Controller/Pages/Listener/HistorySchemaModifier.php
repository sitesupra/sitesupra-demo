<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Supra\Database\Doctrine\Type;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Supra\Controller\Pages\Annotation;
use Supra\Controller\Pages\Entity;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Event\OnFlushEventArgs;

class HistorySchemaModifier extends VersionedTableMetadataListener
{
	const TABLE_PREFIX = '_history';
	const ANNOTATION_NS = 'Supra\Controller\Pages\Annotation\\';
	
	private $isOnCreateCall = false;
	
	protected static $versionedEntities = array(
		'Supra\Controller\Pages\Entity\Abstraction\AbstractPage',
		'Supra\Controller\Pages\Entity\Page',
		'Supra\Controller\Pages\Entity\Template',
		'Supra\Controller\Pages\Entity\ApplicationPage',
		'Supra\Controller\Pages\Entity\GroupPage',
		'Supra\Controller\Pages\Entity\TemplateLayout',
	);
			
	/**
	 * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$versionedEntities = array_merge(self::$versionedEntities, parent::$versionedEntities);
		$metadata = $eventArgs->getClassMetadata();
		$className = $metadata->name;
			
		if ($className == Entity\BlockPropertyMetadata::CN()) {
			if (isset($metadata->associationMappings['blockProperty'])) {
				$metadata->associationMappings['blockProperty']['targetToSourceKeyColumns']['revision'] = 'revision';
			}
		}
		
		$name = &$metadata->table['name'];
		if (in_array($className, $versionedEntities) && strpos($name, static::TABLE_PREFIX) === false) {
			$name = $name . static::TABLE_PREFIX;
		}
	}
	
	public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() AS $entity) {
            if ($entity instanceof \Supra\Controller\Pages\Entity\BlockPropertyMetadata) {
				$metadata = $em->getClassMetadata($entity::CN());
				unset($metadata->associationMappings['blockProperty']['targetToSourceKeyColumns']['revision']);
            }
        }
    }
	
	public function setAsCreateCall()
	{
		$this->isOnCreateCall = true;
	}
	

}
