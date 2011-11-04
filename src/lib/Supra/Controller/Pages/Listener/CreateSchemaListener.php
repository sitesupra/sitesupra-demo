<?php

namespace Supra\Controller\Pages\Listener;

use ReflectionClass;
use Doctrine\ORM\Tools\ToolEvents;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\Common\EventSubscriber;
use Supra\Controller\Pages\Entity\Abstraction\AuditedEntity;


class CreateSchemaListener implements EventSubscriber
{

	public function getSubscribedEvents()
	{
		return array(
			ToolEvents::postGenerateSchemaTable,
		);
	}

	public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $eventArgs)
	{ 
		return;
		/*
		$metadata = $eventArgs->getClassMetadata();

		if ($class->isAbstract() && $class->implementsInterface(AuditedEntity::INTERFACE_NAME)) {
			$entityTable = $eventArgs->getClassTable();
			foreach ($entityTable->getColumns() AS $column) {
				$columnName = $column->getName();
				if ($columnName == 'revision') {
					return;
				}
			}
			
			$entityTable->addColumn('revision', 'string', array('length' => 40));
		}
		 */
	}

}