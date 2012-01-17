<?php

namespace Supra\Controller\Pages\Listener;

use ReflectionClass;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\Common\EventSubscriber;
use Supra\Controller\Pages\Entity\Abstraction\AuditedEntityInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\PageController;
use Doctrine\Common\EventArgs;
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\Controller\Pages\Entity\Page;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Entity\PageRevisionData;
use Supra\Controller\Pages\Event\PagePublishEventArgs;
use Supra\Controller\Pages\Event\PageDeleteEventArgs;
use Doctrine\ORM\PersistentCollection;
use Supra\Controller\Pages\Event\AuditEvents;
use Supra\Controller\Pages\Listener\AuditCreateSchemaListener;
use Supra\Controller\Pages\Entity\Abstraction\Block;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Controller\Pages\Entity\PageLocalization;


class EntityAuditListener implements EventSubscriber
{
	
	const REVISION_TYPE_INSERT = 1;

	const REVISION_TYPE_UPDATE = 2;
	
	const REVISION_TYPE_DELETE = 3;
	
	// possibly not needed
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
	
	/**
	 * @var string
	 */
	private $staticRevisionId;
	
	private $_pageDeleteState = false;
	private $_pageRestoreState = false;

	/**
	 *
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(
			Events::onFlush,
			Events::postUpdate,
			Events::postPersist,
			
			AuditEvents::pagePublishEvent,
			
			AuditEvents::pagePreDeleteEvent,
			AuditEvents::pagePostDeleteEvent,
			
			AuditEvents::pagePreRestoreEvent,
			AuditEvents::pagePostRestoreEvent,
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
		} elseif ($eventArgs instanceof PagePublishEventArgs) {
			$this->em = $eventArgs->getEntityManager();
		} else {
			throw new \LogicException("Unknown event args received");
		}
		
		$this->uow = $this->em->getUnitOfWork();
		$this->conn = $this->em->getConnection();
		$this->platform = $this->conn->getDatabasePlatform();
	}
	
	/**
	 *
	 * @param LifecycleEventArgs $eventArgs 
	 */
	public function postPersist(LifecycleEventArgs $eventArgs)
	{
		if ($this->_pageRestoreState) {
			return;
		}
		
		$this->prepareEnvironment($eventArgs);
				
		$entity = $eventArgs->getEntity();
		
		$this->insertAuditRecord($entity, self::REVISION_TYPE_INSERT);
	}

	/**
	 *
	 * @param LifecycleEventArgs $eventArgs
	 */
	public function postUpdate(LifecycleEventArgs $eventArgs)
	{
		if ($this->_pageRestoreState) {
			return;
		}
		
		$this->prepareEnvironment($eventArgs);
		$entity = $eventArgs->getEntity();
		
		$changeSet = $this->uow->getEntityChangeSet($entity);
		foreach($changeSet as $fieldName => $fieldValue) {
			// trying to avoid useless audit records:
			//   - when Localization lock/unlock action is performed (lock column value update)
			if ($fieldValue instanceof PersistentCollection
					|| ($entity instanceof Localization && $fieldName == 'lock')) {
				unset($changeSet[$fieldName]);
			}
		}

		if ( ! empty($changeSet)) {
			$this->insertAuditRecord($entity, self::REVISION_TYPE_UPDATE);
		}
	}
	
	/**
	 *
	 * @param OnFlushEventArgs $eventArgs 
	 */
	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		if ($this->_pageRestoreState) {
			return;
		}
		
		$this->prepareEnvironment($eventArgs);

		$visitedIds = array();
		foreach ($this->uow->getScheduledEntityDeletions() AS $entity) {
			if ( ! in_array($entity->getId(), $visitedIds)) {
				
				$revisionType = self::REVISION_TYPE_DELETE;
				if ($this->_pageDeleteState) {
					$revisionType = self::REVISION_TYPE_COPY;
				}
				
				$this->insertAuditRecord($entity, $revisionType);
				$visitedIds[] = $entity->getId();
			}
		}
	}
	
	/**
	 * 
	 * @param Entity $entity
	 * @param integer $revisionType
	 */
	private function insertAuditRecord($entity, $revisionType)
	{
		if ( ! $entity instanceof AuditedEntityInterface) {
			return;
		}
		
		$class = $this->auditEm->getClassMetadata(get_class($entity));
		
		$originalEntityData = $this->uow->getOriginalEntityData($entity);
		
		$this->saveRevisionEntityData($class, $originalEntityData, $revisionType);
	}

	/**
	 * 
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
	 * @param string $revisionType
	 */
	private function saveRevisionEntityData(ClassMetadata $class, $entityData, $revisionType)
	{
		// manually add revision_type column/value to query
		$names = array(AuditCreateSchemaListener::REVISION_TYPE_COLUMN_NAME);
		$params = array($revisionType);
		$types = array(\PDO::PARAM_INT);
		
		$classFields = $class->fieldNames;
		// two special cases for revision id:
		//   - if we are creating full COPY of page (publish/trash), 
		//	   then we should use single revision id for all auditing entities
		//   - if entity is deleted, then we need to generate new revision id, or there
		//     will be primary-key collisions inside audit schema in cases when
		//     when entity will be restored and deleted again
		if ($revisionType == self::REVISION_TYPE_COPY || $revisionType == self::REVISION_TYPE_DELETE) {
			$names[] = AuditCreateSchemaListener::REVISION_COLUMN_NAME;
			$params[] = $this->_getRevisionId();
			$types[] = \PDO::PARAM_STR;
			
			unset($classFields[AuditCreateSchemaListener::REVISION_COLUMN_NAME]);
		}
		
		// recursively store parent also if entity is defined as not single-inherited
		if ($class->name != $class->rootEntityName 
				&& $class->inheritanceType != ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE) {
			
			$rootClass = $this->auditEm->getClassMetadata($class->rootEntityName);
			$rootClass->discriminatorValue = $class->discriminatorValue;
			$this->saveRevisionEntityData($rootClass, $entityData, $revisionType);
		}

		foreach ($classFields as $columnName => $field) {

			if ($class->inheritanceType != ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE 
					&&	$class->isInheritedField($field)
					&& ! $class->isIdentifier($field)
					&& $columnName != AuditCreateSchemaListener::REVISION_COLUMN_NAME) {
				continue;
			}
			
			$names[] = $columnName;
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
		if ($class->inheritanceType == ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE
				|| ($class->inheritanceType == ClassMetadata::INHERITANCE_TYPE_JOINED 
					&& $class->name == $class->rootEntityName)) {
			$names[] = $class->discriminatorColumn['name'];
			$params[] = $class->discriminatorValue;
			$types[] = $class->discriminatorColumn['type'];
		}
		
		$insertRevisionSql = $this->getInsertRevisionSQL($class, $names);
		
		$namedParams = array_combine($names, $params);
		$namedTypes = array_combine($names, $types);
		
		$this->conn->executeUpdate($insertRevisionSql, $namedParams, $namedTypes);
	}
	
	/**
	 * @param array $eventArgs 
	 */
	public function pagePublishEvent(PagePublishEventArgs $eventArgs) 
	{
		$this->prepareEnvironment($eventArgs);
		
		$localizationId = $eventArgs->getLocalizationId();
		$userId = $eventArgs->getUserId();
		
		$revisionData = new PageRevisionData();
		$revisionData->setUser($userId);
		$revisionData->setType(PageRevisionData::TYPE_HISTORY);
		$revisionData->setReferenceId($localizationId);
		
		$this->em->persist($revisionData);
		
		$this->staticRevisionId = $revisionData->getId();

		$this->auditEm->getProxyFactory()
				->getProxy(Entity\ReferencedElement\LinkReferencedElement::CN(), -1);
		
		// page single localization
		$localization = $this->em->find(Localization::CN(), $localizationId);
		$this->insertAuditRecord($localization, self::REVISION_TYPE_COPY);
		
		// page localization redirect
		if ($localization instanceof PageLocalization) {
			$redirect = $localization->getRedirect();
			if ( ! is_null($redirect)) {
				$this->insertAuditRecord($redirect, self::REVISION_TYPE_COPY);
			}
		}
		
		// page itself
		$page = $localization->getMaster();
		$this->insertAuditRecord($page, self::REVISION_TYPE_COPY);
		
		// page placeholders
		$placeHolders = $localization->getPlaceHolders();
		foreach ($placeHolders as $placeHolder) {
			$this->insertAuditRecord($placeHolder, self::REVISION_TYPE_COPY);
		}
		
		// page blocks
		$blockIdCollection = $eventArgs->getBlockIdCollection();
		foreach($blockIdCollection as $blockId) {
			$block = $this->em->find(Block::CN(), $blockId);
			$this->insertAuditRecord($block, self::REVISION_TYPE_COPY);
		}
		
		// block properties
		$blockPropertyIdCollection = $eventArgs->getBlockPropertyIdCollection();
		foreach($blockPropertyIdCollection as $propertyId) {
			$property = $this->em->find(BlockProperty::CN(), $propertyId);
			$this->insertAuditRecord($property, self::REVISION_TYPE_COPY);
			
			$metadata = $property->getMetadata();
			foreach($metadata as $metadataItem) {
				$referencedElement = $metadataItem->getReferencedElement();
				$this->insertAuditRecord($referencedElement, self::REVISION_TYPE_COPY);
				$this->insertAuditRecord($metadataItem, self::REVISION_TYPE_COPY);
			}
		}
		
		// to persist revision data
		$this->em->flush();
		
	}
	
	/**
	 * Prepare Audit listener for draft page delete event
	 */
	public function pagePreDeleteEvent(PageDeleteEventArgs $eventArgs) 
	{
		$this->_pageDeleteState = true;
		$pageId = $eventArgs->getPageId();
		
		$revisionData = new PageRevisionData();
		$revisionData->setUser('fix-me-i-have-no-user');
		$revisionData->setType(PageRevisionData::TYPE_TRASH);
		$revisionData->setReferenceId($pageId);
		
		$em = ObjectRepository::getEntityManager('#cms');
		$em->persist($revisionData);
		$em->flush();
		
		$this->staticRevisionId = $revisionData->getId();
		
	}
	
	/**
	 * @return string
	 */
	private function _getRevisionId()
	{
		if (isset($this->staticRevisionId)) {
			return $this->staticRevisionId;
		}
		return md5(uniqid());
	}
	
	public function pagePostDeleteEvent() 
	{
		$this->_pageDeleteState = false;
		$this->staticRevisionId = null;
	}
	
	public function pagePreRestoreEvent() 
	{
		$this->_pageRestoreState = true;
	}
	
	public function pagePostRestoreEvent() 
	{
		$this->_pageRestoreState = false;
	}

}