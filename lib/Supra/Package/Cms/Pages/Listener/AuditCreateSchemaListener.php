<?php

namespace Supra\Package\Cms\Pages\Listener;

use Doctrine\DBAL\Schema\Column;
use ReflectionClass;
use Doctrine\ORM\Tools\ToolEvents;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Supra\Package\Cms\Entity\Abstraction\AuditedEntityInterface;

class AuditCreateSchemaListener implements EventSubscriber
{
	const AUDIT_SUFFIX = '_audit';
	const REVISION_COLUMN_NAME = 'revision';
	const REVISION_TYPE_COLUMN_NAME = 'revision_type';
	const REVISION_TYPE_FIELD_NAME = 'revisionType';
	
	public function getSubscribedEvents()
	{
		return array(
			ToolEvents::postGenerateSchema,
			ToolEvents::postGenerateSchemaTable,
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

			if (strrpos($tableName, self::AUDIT_SUFFIX) !== strlen($tableName) - strlen(self::AUDIT_SUFFIX)) {
				$schema->dropTable($tableName);
			}
		}
	}

	public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $eventArgs)
	{
		$metadata = $eventArgs->getClassMetadata();
		$class = new ReflectionClass($metadata->name);
		$schema = $eventArgs->getSchema();
		$entityTable = $eventArgs->getClassTable();
		$tableName = $entityTable->getName();

		if ($class->implementsInterface(AuditedEntityInterface::AUDITED_ENTITY_INTERFACE)) {

			// Recreate the table inside the schema
			$schema->dropTable($tableName);
			$revisionTable = $schema->createTable($tableName);
			
			foreach ($entityTable->getColumns() AS $column) {
				
				// All fields are not mandatory in the audit
				$notNull = false;
				
				/* @var $column Column */
				if ($column->getName() == self::REVISION_COLUMN_NAME) {
					continue;
				}
				
				if ($column->getName() == self::REVISION_TYPE_COLUMN_NAME) {
					$notNull = true;
				}
				
				$revisionTable->addColumn($column->getName(), $column->getType()->getName(), array_merge(
					$column->toArray(),
					array('notnull' => $notNull, 'autoincrement' => false)
				));
			}
			
			$revisionTable->addColumn(self::REVISION_COLUMN_NAME, 'string', array('length' => 20));
			
			$pkColumns = $entityTable->getPrimaryKey()
					->getColumns();
			
			if ( ! in_array(self::REVISION_COLUMN_NAME, $pkColumns)) {
				$pkColumns[] = self::REVISION_COLUMN_NAME;
			}
			
			$revisionTable->setPrimaryKey($pkColumns);
			
		// Don't need any other tables in the audit schema
		} else {
			$schema->dropTable($tableName);
		}
	}

	/**
	 * Will add the _audit suffix
	 * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$classMetadata = $eventArgs->getClassMetadata();
		$className = $classMetadata->name;
		$name = &$classMetadata->table['name'];
		$class = new ReflectionClass($className);

		// composite id is required for audited entities, otherwise LEFT JOIN 
		// queries on joined inheritance tables will cause huge result set
		if ($class->implementsInterface(AuditedEntityInterface::AUDITED_ENTITY_INTERFACE)) {
			$classMetadata->setIdentifier(array('id', 'revision'));

			if (empty($classMetadata->parentClasses)) {
				// Add the revision_type column
				$classMetadata->mapField(
					array(
						'fieldName' => self::REVISION_TYPE_FIELD_NAME,
						'type' => 'smallint',
						'nullable' => false,
						'columnName' => self::REVISION_TYPE_COLUMN_NAME,
					)
				);
			}
		}

		if ($class->implementsInterface(AuditedEntityInterface::AUDITED_ENTITY_INTERFACE)) {
			if (strpos($name, self::AUDIT_SUFFIX) === false) {
				$name = $name . self::AUDIT_SUFFIX;
			}
		}

		//$versionedDraftEntities = TableDraftSuffixAppender::getVersionedEntities();
		
		// if entity is audited, then it should be loaded from "*_audit" tables
		//if ($class->implementsInterface(AuditedEntity::AUDITED_ENTITY_INTERFACE)) {
		//}
		// all other versioned entities should be loaded from "*_draft" tables
		//else if (in_array($className, $versionedDraftEntities) && strpos($name, TableDraftSuffixAppender::TABLE_SUFFIX) === false) {
		//	$name = $name . TableDraftSuffixAppender::TABLE_SUFFIX;
		//}
	}
}