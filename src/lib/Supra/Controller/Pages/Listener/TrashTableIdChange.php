<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Id\AssignedGenerator;

/**
 * Sets ID auto generation off for trash pages
 */
class TrashTableIdChange extends VersionedTableMetadataListener
{
	
	protected static $versionedEntities = array(
		'Supra\Controller\Pages\Entity\Abstraction\AbstractPage',
		'Supra\Controller\Pages\Entity\Page',
	);
	
	/**
	 * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$versionedEntities = array_merge(self::$versionedEntities, parent::$versionedEntities);
		
		$classMetadata = $eventArgs->getClassMetadata();
		$className = $classMetadata->name;
		
		if (in_array($className, $versionedEntities)) {
			$idField = $classMetadata->fieldMappings['id'];
			$classMetadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
			$classMetadata->setIdGenerator(new AssignedGenerator());
			
			unset($classMetadata->table['uniqueConstraints']['locale_path_idx']);
		}
	}
}
