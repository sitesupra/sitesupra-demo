<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Id\AssignedGenerator;

/**
 * Removes lock ID column from public and trash tables
 */
class VersionedTableLockIdRemover
{
	
	protected static $versionedEntities = array(
		'Supra\Controller\Pages\Entity\Abstraction\Localization',
		'Supra\Controller\Pages\Entity\PageLocalization',
		'Supra\Controller\Pages\Entity\TemplateLocalization',
	);
	
	/**
	 * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$classMetadata = $eventArgs->getClassMetadata();
		$className = $classMetadata->name;
		
		
		if (in_array($className, static::$versionedEntities)) {
			unset($classMetadata->associationMappings['lock']);
			unset($classMetadata->reflFields['lock']);
		}
	}
}
