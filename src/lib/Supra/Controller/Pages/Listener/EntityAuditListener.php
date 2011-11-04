<?php

namespace Supra\Controller\Pages\Listener;

use ReflectionClass;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\Common\EventSubscriber;
use Supra\Controller\Pages\Entity\Abstraction\AuditedEntity;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Supra\ObjectRepository\ObjectRepository;


class EntityAuditListener implements EventSubscriber
{
	
	const REVISION_TYPE_INSERT = 1;

	const REVISION_TYPE_UPDATE = 2;
	
	const REVISION_TYPE_DELETE = 3;
	
	const REVISION_TYPE_COPY = 4;
	
	/**
	 * @var Doctrine\DBAL\Connection
	 */
	private $conn;

	/**
	 * @var Doctrine\DBAL\Platforms\AbstractPlatform
	 */
	private $platform;

	/**
	 * @var Doctrine\ORM\EntityManager
	 */
	private $em;
	
	/**
	 * @var Doctrine\ORM\EntityManager
	 */
	private $auditEm;

	/**
	 * @var array
	 */
	private $insertRevisionSQL = array();

	/**
	 * @var Doctrine\ORM\UnitOfWork
	 */
	private $uow;


	public function getSubscribedEvents()
	{
		return array(
			Events::onFlush,
			Events::postUpdate,
			Events::postPersist,
		);
	}

	public function postPersist(LifecycleEventArgs $eventArgs)
	{
		$entity = $eventArgs->getEntity();
		if ( ! ($entity instanceof AuditedEntity)) {
			return;
		}
		
		$class = $this->auditEm->getClassMetadata($entity::CN());
		
		$this->saveRevisionEntityData($class, $this->uow->getOriginalEntityData($entity), self::REVISION_TYPE_INSERT);
	}

	public function postUpdate(LifecycleEventArgs $eventArgs)
	{
		$entity = $eventArgs->getEntity();
		if ( ! ($entity instanceof AuditedEntity)) {
			return;
		}
		
		$class = $this->auditEm->getClassMetadata($entity::CN());

		//$originalData = $this->uow->getOriginalEntityData($entity);
		$changeSet = $this->uow->getEntityChangeSet($entity);
		
		$entityData = array_merge($this->uow->getOriginalEntityData($entity), $this->uow->getEntityIdentifier($entity));
		$this->saveRevisionEntityData($class, $entityData, self::REVISION_TYPE_UPDATE);
	}

	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		$this->em = $eventArgs->getEntityManager();
		$this->conn = $this->em->getConnection();
		$this->uow = $this->em->getUnitOfWork();
		$this->platform = $this->conn->getDatabasePlatform();

		// Using Audit-EM provided class metadatas, we can escape fields
		// that were mapped for another schemas (for example - `lock_id` in Draft)
		$this->auditEm = ObjectRepository::getEntityManager('#audit');

		foreach ($this->uow->getScheduledEntityDeletions() AS $entity) {
			if ( ! ($entity instanceof AuditedEntity)) {
				return;
			}
			
			$class = $this->auditEm->getClassMetadata($entity::CN());
			
			$entityData = array_merge($this->uow->getOriginalEntityData($entity), $this->uow->getEntityIdentifier($entity));
			$this->saveRevisionEntityData($class, $entityData, self::REVISION_TYPE_DELETE);
		}
	}

	private function getRevisionId()
	{
		// Audited entities will receive their IDs from actual entities by, ex: getRevisionId();
		// But actuall entites will receive their ids when they will be persisted/updated
		// On persist: assign an revision id
		// On update: rewrite! current revision id, but item with old revision id will be stored here, in audit log
		// YEAH!
		
		// Need to test: Is it long, to find all revisions and show, for example, histored version of single page
		// Need to think about - how to make page `histored` copy
		//		- create, here, in listener, custom event (ex: pagePublishEvent), that will store page items with single revision IDs
		// Need to think about - how to create deleted version of page? like custom publish event and mark inside revision data, that it was page delete event?
		
		// As we are not creating any new revision id, we are not required to create something inside revision data table
		return 'dummy-' . md5(uniqid('sha', true));
	}
	
	/**
	 * @param ClassMetadata $class
	 * @return string 
	 */
	private function getInsertRevisionSQL($class)
	{
		if (!isset($this->insertRevisionSQL[$class->name])) {

			// FIXME: removing draft prefix from audit table names, as they, actually, not draft
			$tableName = str_replace('_draft', '', $class->table['name']) . '_audit';
			
			//$sql = "INSERT INTO " . $tableName . " (" .	'revision, revision_type';
			
			$sql = "INSERT INTO " . $tableName . " (" .	'revision_type';
			
			$columnCount = 0;
			
			
			if ($class->isInheritedField('revision')) {
				$sql .= ', revision';
				$columnCount++;
			}
			
			
			foreach ($class->fieldNames AS $field) {
				
				if (isset($class->fieldMappings[$field]['inherited'])
						&& ! $class->isIdentifier($field)) {
					continue;
				}

				$columnCount++;
				$sql .= ', ' . $class->getQuotedColumnName($field, $this->platform);
			}
			foreach ($class->associationMappings AS $assoc) {
				if ( ($assoc['type'] & ClassMetadata::TO_ONE) > 0 && $assoc['isOwningSide']) {
					foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
						$sql .= ', ' . $sourceCol;
						$columnCount++;
					}
				}
			}
			if ($class->inheritanceType == ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE) {
				$sql .= ', ' . $class->discriminatorColumn['name'];
				$columnCount++;
			}
			
			//$sql .= ") VALUES (" . implode(", ", array_fill(0, $columnCount+2, '?')) . ")";
			$sql .= ") VALUES (" . implode(", ", array_fill(0, $columnCount+1, '?')) . ")";
			$this->insertRevisionSQL[$class->name] = $sql;
		}
		return $this->insertRevisionSQL[$class->name];
	}

	/**
	 * @param ClassMetadata $class
	 * @param array $entityData
	 * @param string $revType
	 */
	private function saveRevisionEntityData($class, $entityData, $revType)
	{
		//$params = array($this->getRevisionId(), $revType);
		//$types = array(\PDO::PARAM_STR, \PDO::PARAM_INT);
		
		$params = array($revType);
		$types = array(\PDO::PARAM_INT);
		
		if ($class->isInheritedField('revision')) {
			$params[] = $this->getRevisionId();
			$types[] = \PDO::PARAM_STR;
		}
		
		foreach ($class->fieldNames AS $field) {
			
			if (isset($class->fieldMappings[$field]['inherited'])
					&& ! $class->isIdentifier($field)) {
				continue;
			}
			
			$params[] = $entityData[$field];
			$types[] = $class->fieldMappings[$field]['type'];
		}
		foreach ($class->associationMappings AS $field => $assoc) {
			if (($assoc['type'] & ClassMetadata::TO_ONE) > 0 && $assoc['isOwningSide']) {
				$targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

				if ($entityData[$field] !== null) {
					$relatedId = $this->uow->getEntityIdentifier($entityData[$field]);
				}

				$targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

				foreach ($assoc['sourceToTargetKeyColumns'] as $sourceColumn => $targetColumn) {
					if ($entityData[$field] === null) {
						$params[] = null;
						$types[] = \PDO::PARAM_STR;
					} else {
						$params[] = $relatedId[$targetClass->fieldNames[$targetColumn]];
						$types[] = $targetClass->getTypeOfColumn($targetColumn);
					}
				}
			}
		}
		if ($class->inheritanceType == ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE) {
			$params[] = $class->discriminatorValue;
			$types[] = $class->discriminatorColumn['type'];
		}
		
		$this->conn->executeUpdate($this->getInsertRevisionSQL($class), $params, $types);
	}
	
	public function pagePublishEvent()
	{
		// This event will be fired on page publish action
		// save page copy to audit with one revision id
		// this is necessary, to fast-find of page `histored` copy
		// and revision type = copy
	} 
	
}