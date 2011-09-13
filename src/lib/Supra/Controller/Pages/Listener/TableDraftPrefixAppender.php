<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * Adds draft prefix for the tables from the schema for draft connection only
 */
class TableDraftPrefixAppender extends VersionedTableMetadataListener
{
	/**
	 * Draft table name prefix
	 */
	const TABLE_PREFIX = '_draft';
	
	/**
	 * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$classMetadata = $eventArgs->getClassMetadata();
		$className = $classMetadata->name;
		$name = &$classMetadata->table['name'];
		
		if (in_array($className, static::$versionedEntities) && strpos($name, static::TABLE_PREFIX) === false) {
			$name = $name . static::TABLE_PREFIX;
		}
	}
}
