<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Id\AssignedGenerator;

/**
 * Sets ID auto generation off for public pages
 */
class PublicVersionedTableIdChange extends VersionedTableMetadataListener
{
	/**
	 * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		//TODO: this can be removed because is generated using sha1 now
//		$classMetadata = $eventArgs->getClassMetadata();
//		$className = $classMetadata->name;
//		
//		if (in_array($className, static::$versionedEntities)) {
//			$idField = $classMetadata->fieldMappings['id'];
//			$classMetadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
//			$classMetadata->setIdGenerator(new AssignedGenerator());
//		}
	}
}
