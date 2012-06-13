<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\ToolEvents;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

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
	
	public static function getVersionedEntities()
	{
		return array_merge(parent::$versionedEntities, self::$versionedEntities);
	}
	
	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(
			ToolEvents::postGenerateSchema,
			Events::loadClassMetadata
		);
	}
	
	/**
	 * Removes common tables
	 * @param GenerateSchemaEventArgs $eventArgs
	 */
	public function postGenerateSchema(GenerateSchemaEventArgs $eventArgs)
	{
		$schema = $eventArgs->getSchema();
		$tables = $schema->getTables();
		
		foreach ($tables as $entityTable) {
			$tableName = $entityTable->getName();

			if (strrpos($tableName, self::TABLE_SUFFIX) !== strlen($tableName) - strlen(self::TABLE_SUFFIX)) {
				$schema->dropTable($tableName);
			}
		}
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
