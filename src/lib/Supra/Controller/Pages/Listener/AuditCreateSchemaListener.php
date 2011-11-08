<?php

namespace Supra\Controller\Pages\Listener;

use ReflectionClass;
use Doctrine\ORM\Tools\ToolEvents;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\Common\EventSubscriber;
use Supra\Controller\Pages\Entity\Abstraction\AuditedEntity;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;

class AuditCreateSchemaListener implements EventSubscriber
{
	const AUDIT_SUFFIX = '_audit';
	
	private $config;
	
	public function __construct()
	{
		$this->config = array(
			'audit_suffix' => self::AUDIT_SUFFIX,
		);
	}

	public function getSubscribedEvents()
	{
		return array(
			ToolEvents::postGenerateSchemaTable,
//			ToolEvents::postGenerateSchema,
			Events::loadClassMetadata
		);
	}

	public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $eventArgs)
	{
		$metadata = $eventArgs->getClassMetadata();
		$class = new ReflectionClass($metadata->name);
		$schema = $eventArgs->getSchema();
		$entityTable = $eventArgs->getClassTable();
		$tableName = $entityTable->getName();
		
		if ($class->implementsInterface(AuditedEntity::INTERFACE_NAME)) {
			
			// Recreate the table inside the schema
			$schema->dropTable($tableName);
			$revisionTable = $schema->createTable($tableName);
			
			foreach ($entityTable->getColumns() AS $column) {
				/* @var $column Column */
				
				// FIXME: ugly... or not?
				if ($column->getName() == 'revision') {
					continue;
				}
				
				$revisionTable->addColumn($column->getName(), $column->getType()->getName(), array_merge(
					$column->toArray(),
					array('notnull' => false, 'autoincrement' => false)
				));
			}
			
			$revisionTable->addColumn('revision', 'string', array('length' => 40));
			$revisionTable->addColumn('revision_type', 'smallint', array('length' => 1));
			
			$pkColumns = $entityTable->getPrimaryKey()
					->getColumns();
			
			$pkColumns[] = 'revision';
			$revisionTable->setPrimaryKey($pkColumns);
			
		// Don't need any other tables in the audit schema
		} else {
			$schema->dropTable($tableName);
		}
	}

	public function postGenerateSchema(GenerateSchemaEventArgs $eventArgs)
	{
		return;
		/*
		$schema = $eventArgs->getSchema();
		$revisionsTable = $schema->createTable($this->config['revision_table_name']);
		$revisionsTable->addColumn('id', 'integer', array(
			'autoincrement' => true,
		));
		$revisionsTable->addColumn('timestamp', 'datetime');
		$revisionsTable->addColumn('username', 'string');
		$revisionsTable->setPrimaryKey(array('id'));
	
		 */
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
		
		if ($class->implementsInterface(AuditedEntity::INTERFACE_NAME) && strpos($name, self::AUDIT_SUFFIX) === false) {
			$name = $name . self::AUDIT_SUFFIX;
		}
	}
}