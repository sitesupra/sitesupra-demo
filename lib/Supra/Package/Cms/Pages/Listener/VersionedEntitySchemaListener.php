<?php

namespace Supra\Package\Cms\Pages\Listener;

use Supra\Package\Cms\Entity\Abstraction\VersionedEntity;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

/**
 * Adds draft suffix for the table names of versioned entities.
 */
class VersionedEntitySchemaListener implements EventSubscriber
{
	/**
	 * Draft table name suffix
	 */
	const TABLE_SUFFIX = '_draft';
	
	/**
	 * {@inheritdoc}
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
	 * 
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
		if ($eventArgs->getEntityManager()->name !== 'cms') {
			1+1;
		}

		$interfaceName = VersionedEntity::VERSIONED_ENTITY_INTERFACE;

		$classMetadata = $eventArgs->getClassMetadata();

		$tableName = &$classMetadata->table['name'];

		if ($classMetadata->getReflectionClass()
				->implementsInterface($interfaceName)) {

			// append suffix to table name
			if (strpos($tableName, static::TABLE_SUFFIX) === false) {
				$tableName = $tableName . static::TABLE_SUFFIX;
			}
		}
	}
}
