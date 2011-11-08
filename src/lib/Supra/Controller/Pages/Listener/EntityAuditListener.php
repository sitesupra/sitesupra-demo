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
use Supra\Controller\Pages\PageController;
use Doctrine\Common\EventArgs;

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
	
	/**
	 * Prepares local environment
	 * @param EventArgs $eventArgs
	 */
	private function prepareEnvironment(EventArgs $eventArgs)
	{
		// Using Audit-EM provided class metadatas, we can escape fields
		// that were mapped for another schemas (for example - `lock_id` in Draft)
		$this->auditEm = ObjectRepository::getEntityManager(PageController::SCHEMA_AUDIT);
		
		if ($eventArgs instanceof LifecycleEventArgs) {
			$this->em = $eventArgs->getEntityManager();
		} elseif ($eventArgs instanceof OnFlushEventArgs) {
			$this->em = $eventArgs->getEntityManager();
		} else {
			throw new \LogicException("Unknown event args received");
		}
		
		$this->uow = $this->em->getUnitOfWork();
		$this->conn = $this->em->getConnection();
		$this->platform = $this->conn->getDatabasePlatform();
	}
	
	public function postPersist(LifecycleEventArgs $eventArgs)
	{
		$this->prepareEnvironment($eventArgs);
		$entity = $eventArgs->getEntity();
		
		$this->insertAuditRecord($entity, self::REVISION_TYPE_INSERT);
	}

	public function postUpdate(LifecycleEventArgs $eventArgs)
	{
		$this->prepareEnvironment($eventArgs);
		$entity = $eventArgs->getEntity();
		
		$this->insertAuditRecord($entity, self::REVISION_TYPE_UPDATE);
		
//		if ( ! ($entity instanceof AuditedEntity)) {
//			return;
//		}
//		
//		$class = $this->auditEm->getClassMetadata($entity::CN());
//
//		//$originalData = $this->uow->getOriginalEntityData($entity);
//		$changeSet = $this->uow->getEntityChangeSet($entity);
//		
//		$originalEntityData = $this->uow->getOriginalEntityData($entity);
//		$entityIdentifier = $this->uow->getEntityIdentifier($entity);
//		
//		$entityData = array_merge($originalEntityData, $entityIdentifier);
//		$this->saveRevisionEntityData($class, $entityData, self::REVISION_TYPE_UPDATE);
	}

	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		$this->prepareEnvironment($eventArgs);

		foreach ($this->uow->getScheduledEntityDeletions() AS $entity) {
			
			$this->insertAuditRecord($entity, self::REVISION_TYPE_DELETE);
			
//			if ( ! ($entity instanceof AuditedEntity)) {
//				return;
//			}
//			
//			$class = $this->auditEm->getClassMetadata($entity::CN());
//			
//			$originalEntityData = $this->uow->getOriginalEntityData($entity);
//			$entityIdentifier = $this->uow->getEntityIdentifier($entity);
//			
//			$entityData = array_merge($originalEntityData, $entityIdentifier);
//			$this->saveRevisionEntityData($class, $entityData, self::REVISION_TYPE_DELETE);
		}
	}
	
	private function insertAuditRecord($entity, $revisionType)
	{
		if ( ! $entity instanceof AuditedEntity) {
			return;
		}
		
		$class = $this->auditEm->getClassMetadata(get_class($entity));
		
		//TODO: fetch current data
		$originalEntityData = $this->uow->getOriginalEntityData($entity);
//		$entityIdentifier = $this->uow->getEntityIdentifier($entity);
//		$entityData = array_merge($originalEntityData);
		
//		$entityData = $this->uow->get
		
		$this->saveRevisionEntityData($class, $originalEntityData, $revisionType);
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
	 * @param array $fieldNames
	 * @return string 
	 */
	private function getInsertRevisionSQL(ClassMetadata $class, array $fieldNames)
	{
		$tableName = $class->table['name'];

		$sql = 'INSERT INTO ' . $tableName
				. ' (' . implode(', ', $fieldNames) . ')'
				. ' VALUES (:' . implode(', :', $fieldNames) . ')';

		return $sql;
	}

	/**
	 * @param ClassMetadata $class
	 * @param array $entityData
	 * @param string $revType
	 */
	private function saveRevisionEntityData(ClassMetadata $class, $entityData, $revType)
	{
		//$params = array($this->getRevisionId(), $revType);
		//$types = array(\PDO::PARAM_STR, \PDO::PARAM_INT);
		
		$names = array('revision_type');
		$params = array($revType);
		$types = array(\PDO::PARAM_INT);
		
		if ($class->isInheritedField('revision')) {
			$names[] = 'revision';
			$params[] = $this->getRevisionId();
			$types[] = \PDO::PARAM_STR;
		}
		
		foreach ($class->fieldNames AS $field) {
			
			if ($class->isInheritedField($field)
					&& ! $class->isIdentifier($field)) {
				continue;
			}
			
			$names[] = $field;
			$params[] = $entityData[$field];
			$types[] = $class->fieldMappings[$field]['type'];
		}
		
		foreach ($class->associationMappings AS $field => $assoc) {
			if ($class->isSingleValuedAssociation($field) && $assoc['isOwningSide']) {
				$targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

				// Has value
				if ($entityData[$field] !== null) {
					$relatedId = $this->uow->getEntityIdentifier($entityData[$field]); // Or simply $entityData[$field]->getId()

					foreach ($assoc['sourceToTargetKeyColumns'] as $sourceColumn => $targetColumn) {
						$names[] = $sourceColumn;
						$params[] = $relatedId[$targetClass->getFieldName($targetColumn)];
						$types[] = $targetClass->getTypeOfColumn($targetColumn);
					}
				
				// Null
				} else {
					foreach ($assoc['sourceToTargetKeyColumns'] as $sourceColumn => $targetColumn) {
						$names[] = $sourceColumn;
						$params[] = null;
						$types[] = \PDO::PARAM_STR;
					}
				}
			}
		}
		
		// Discriminator
		if ($class->inheritanceType == ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE) {
			$names[] = $class->discriminatorColumn['name'];
			$params[] = $class->discriminatorValue;
			$types[] = $class->discriminatorColumn['type'];
		}
		
		$insertRevisionSql = $this->getInsertRevisionSQL($class, $names);
		
		$namedParams = array_combine($names, $params);
		$namedTypes = array_combine($names, $types);
		
		$this->conn->executeUpdate($insertRevisionSql, $namedParams, $namedTypes);
	}
	
	public function pagePublishEvent()
	{
		// This event will be fired on page publish action
		// save page copy to audit with one revision id
		// this is necessary, to fast-find of page `histored` copy
		// and revision type = copy
	} 
	
}