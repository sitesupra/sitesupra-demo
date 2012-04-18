<?php

namespace Supra\Controller\Pages\Listener;

use ReflectionClass;
use Doctrine\ORM\Tools\ToolEvents;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\Common\EventSubscriber;
use Supra\Controller\Pages\Entity\Abstraction\AuditedEntityInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;

class AuditCreateSchemaListener implements EventSubscriber
{
	const AUDIT_SUFFIX = '_audit';
	const REVISION_COLUMN_NAME = 'revision';
	const REVISION_TYPE_COLUMN_NAME = 'revision_type';
	
	
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

		if ($class->implementsInterface(AuditedEntityInterface::INTERFACE_NAME)) {
			
			// Recreate the table inside the schema
			$schema->dropTable($tableName);
			$revisionTable = $schema->createTable($tableName);
			
			foreach ($entityTable->getColumns() AS $column) {
				
				/* @var $column Column */
				if ($column->getName() == self::REVISION_COLUMN_NAME) {
					continue;
				}
				
				$revisionTable->addColumn($column->getName(), $column->getType()->getName(), array_merge(
					$column->toArray(),
					array('notnull' => false, 'autoincrement' => false)
				));
			}
			
			$revisionTable->addColumn(self::REVISION_COLUMN_NAME, 'string', array('length' => 20));
			$revisionTable->addColumn(self::REVISION_TYPE_COLUMN_NAME, 'smallint', array('length' => 1));
			
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
		
		if ($className == \Supra\Controller\Pages\Entity\ReferencedElement\ReferencedElementAbstract::CN()
				|| $className == \Supra\Controller\Pages\Entity\Abstraction\AbstractPage::CN()) {
			$classMetadata->setIdentifier(array('id', 'revision'));
		}
		
		if ($class->implementsInterface(AuditedEntityInterface::INTERFACE_NAME) && strpos($name, self::AUDIT_SUFFIX) === false) {
			$name = $name . self::AUDIT_SUFFIX;
		} else if ($className == \Supra\Controller\Pages\Entity\PageLocalizationPath::CN()) {
			$name = $name . TableDraftSuffixAppender::TABLE_SUFFIX;
		}
	}
}