<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Id\AssignedGenerator;
use Supra\Controller\Pages\Entity\PageLocalization;

/**
 * Sets ID auto generation off for trash pages
 */
class TrashTableIdChange extends VersionedTableMetadataListener
{
	/**
	 * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$classMetadata = $eventArgs->getClassMetadata();
		$className = $classMetadata->name;
		
		// Remove unique constraint
		if ($className == PageLocalization::CN()) {
			unset($classMetadata->table['uniqueConstraints']['locale_path_idx']);
		}
	}
}
