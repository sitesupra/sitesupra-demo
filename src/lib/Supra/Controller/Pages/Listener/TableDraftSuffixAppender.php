<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;

/**
 * Adds draft suffix for the tables from the schema for draft connection only
 */
class TableDraftSuffixAppender extends VersionedTableMetadataListener implements EventSubscriber
{
	/**
	 * Draft table name suffix
	 */
	const TABLE_SUFFIX = '_draft';
	
	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(Events::loadClassMetadata);
	}
	
	/**
	 * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$classMetadata = $eventArgs->getClassMetadata();
		$className = $classMetadata->name;
		$name = &$classMetadata->table['name'];
		
		if (in_array($className, static::$versionedEntities) && strpos($name, static::TABLE_SUFFIX) === false) {
			$name = $name . static::TABLE_SUFFIX;
		}
	}
}
