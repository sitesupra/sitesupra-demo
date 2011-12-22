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
	 * Entities to be versioned
	 * @var array
	 */
	protected static $versionedEntities = array(
		'Supra\Controller\Pages\Entity\PageLocalizationPath',
	);
	
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
		$versionedEntities = array_merge(parent::$versionedEntities, self::$versionedEntities);
		
		$classMetadata = $eventArgs->getClassMetadata();
		$className = $classMetadata->name;
		$name = &$classMetadata->table['name'];
		
		if (in_array($className, $versionedEntities) && strpos($name, static::TABLE_SUFFIX) === false) {
			$name = $name . static::TABLE_SUFFIX;
		}
	}
}
