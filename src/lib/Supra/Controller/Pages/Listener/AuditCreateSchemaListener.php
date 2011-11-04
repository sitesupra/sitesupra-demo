<?php

namespace Supra\Controller\Pages\Listener;

use ReflectionClass;
use Doctrine\ORM\Tools\ToolEvents;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\Common\EventSubscriber;
use Supra\Controller\Pages\Entity\Abstraction\AuditedEntity;


class AuditCreateSchemaListener implements EventSubscriber
{
	private $config;
	
	public function __construct()
	{
		$this->config = array(
			'audit_suffix' => '_audit',
		);
	}

	public function getSubscribedEvents()
	{
		return array(
			ToolEvents::postGenerateSchemaTable,
			ToolEvents::postGenerateSchema,
		);
	}

	public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $eventArgs)
	{
		$metadata = $eventArgs->getClassMetadata();
		$class = new ReflectionClass($metadata->name);
		
		if ($class->implementsInterface(AuditedEntity::INTERFACE_NAME)) {
			$schema = $eventArgs->getSchema();
			$entityTable = $eventArgs->getClassTable();
			$revisionTable = $schema->createTable(
				$entityTable->getName().$this->config['audit_suffix']
			);
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
}