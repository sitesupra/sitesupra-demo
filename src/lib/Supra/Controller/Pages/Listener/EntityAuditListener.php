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
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\Controller\Pages\Entity\Page;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Entity\RevisionData;

class EntityAuditListener implements EventSubscriber
{
	
	const REVISION_TYPE_INSERT = 1;

	const REVISION_TYPE_UPDATE = 2;
	
	const REVISION_TYPE_DELETE = 3;
	
	// is this is needed?
	const REVISION_TYPE_COPY = 4;
	
	const pagePublishEvent = 'pagePublishEvent';
	const pagePreDeleteEvent = 'pagePreDeleteEvent';
	const pagePostDeleteEvent = 'pagePostDeleteEvent';
	
	const pagePreRestoreEvent = 'pagePreRestoreEvent';
	const pagePostRestoreEvent = 'pagePostRestoreEvent';
	
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
			
			self::pagePublishEvent,
			
			self::pagePreDeleteEvent,
			self::pagePostDeleteEvent,
			
			self::pagePreRestoreEvent,
			self::pagePostRestoreEvent,
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
	
	/**
	 *
	 * @param LifecycleEventArgs $eventArgs 
	 */
	public function postPersist(LifecycleEventArgs $eventArgs)
	{
		if ( ! $this->checkStates()) {
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
		if ( ! $this->checkStates()) {
			return;
		}
		
		$this->prepareEnvironment($eventArgs);
		$entity = $eventArgs->getEntity();
		
		if ($entity instanceof Localization) {
			$changeSet = $this->em
					->getUnitOfWork()
						->getEntityChangeSet($entity);
			
			if (isset($changeSet['lock'])) {
				return;
			}
		}
		
		$this->insertAuditRecord($entity, self::REVISION_TYPE_UPDATE);
	}
	
	/**
	 *
	 * @param OnFlushEventArgs $eventArgs 
	 */
	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		if ( ! $this->checkStates()) {
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
	 * @param Entity $entity
	 * @param integer $revisionType
	 */
	private function insertAuditRecord($entity, $revisionType)
	{
		if ( ! $entity instanceof AuditedEntity) {
			return;
		}
		
		$class = $this->auditEm->getClassMetadata(get_class($entity));
		
		$originalEntityData = $this->uow->getOriginalEntityData($entity);
		
		$this->saveRevisionEntityData($class, $originalEntityData, $revisionType);
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
		$names = array('revision_type');
		$params = array($revType);
		$types = array(\PDO::PARAM_INT);
		
		if ((isset($this->staticRevisionId) && isset($class->fieldNames['revision']))
				|| $revType == self::REVISION_TYPE_DELETE) {
			unset($entityData['revision']);
			unset($class->fieldNames['revision']);
		}
		
		if ( ! isset($class->fieldNames['revision'])) {
			$names[] = 'revision';
			$params[] = $this->_getRevisionId();
			$types[] = \PDO::PARAM_STR;
		}
		
		if ($class->name != $class->rootEntityName 
				&& $class->inheritanceType != ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE) {
			
			$rootClass = $this->auditEm->getClassMetadata($class->rootEntityName);
			$rootClass->discriminatorValue = $class->discriminatorValue;
			$this->saveRevisionEntityData($rootClass, $entityData, $revType);
		}

		foreach ($class->fieldNames as $colmnName => $field) {
			
			if ($class->isInheritedField($field)
					&& $class->inheritanceType != ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE
					&& ! $class->isIdentifier($field)
					&& $field != 'revision') {
				continue;
			}
			
			$names[] = $colmnName;
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
	public function pagePublishEvent($eventArgs) 
	{
		// db::beginTransaction
		$localizationId = $eventArgs['localizationId'];
		$userId = $eventArgs['userId'];
		
		$revisionData = new RevisionData();
		$revisionData->setUser($userId);
		$revisionData->setType(RevisionData::TYPE_HISTORY);
		$revisionData->setLocalizationId($localizationId);
		
		$this->em->persist($revisionData);
		
		$this->staticRevisionId = $revisionData->getId();
		
		// page single localization
		$localization = $this->em->find(Localization::CN(), $localizationId);
		$this->insertAuditRecord($localization, self::REVISION_TYPE_COPY);
		
		// page localization redirect
		if ($localization instanceof Entity\PageLocalization) {
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
		foreach($eventArgs['blocks'] as $blockId) {
			$block = $this->em->find(\Supra\Controller\Pages\Entity\Abstraction\Block::CN(), $blockId);
			$this->insertAuditRecord($block, self::REVISION_TYPE_COPY);
		}
		
		// block properties
		foreach($eventArgs['blockProperties'] as $propertyId) {
			$property = $this->em->find(\Supra\Controller\Pages\Entity\BlockProperty::CN(), $propertyId);
			$this->insertAuditRecord($property, self::REVISION_TYPE_COPY);
			
			$metadata = $property->getMetadata();
			foreach($metadata as $metadataItem) {
				$referencedElement = $metadataItem->getReferencedElement();
				$this->insertAuditRecord($referencedElement, self::REVISION_TYPE_COPY);
				$this->insertAuditRecord($metadataItem, self::REVISION_TYPE_COPY);
			}
		}
		
		// block property metadata
		foreach($eventArgs['blockPropertyMetadatas'] as $metadataId) {
			$propertyMetadata = $this->em->find(\Supra\Controller\Pages\Entity\BlockPropertyMetadata::CN(), $metadataId);
			$this->insertAuditRecord($propertyMetadata, self::REVISION_TYPE_COPY);
		}
		
		// to persist revision data
		$this->em->flush();
		
	}
	
	/**
	 * Prepare Audit listener for draft page delete event
	 */
	public function pagePreDeleteEvent($eventArgs) 
	{
		$this->_pageDeleteState = true;
		$localizationId = $eventArgs['localizationId'];
		
		$revisionData = new RevisionData();
		$revisionData->setUser('fix-me-i-have-no-user');
		$revisionData->setType(RevisionData::TYPE_TRASH);
		$revisionData->setLocalizationId($localizationId);
		
		$em = ObjectRepository::getEntityManager('#cms');
		$em->persist($revisionData);
		$em->flush();
		
		$this->staticRevisionId = $revisionData->getId();
		
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
	
	private function checkStates()
	{
		if ($this->_pageRestoreState) {
			return false;
		}
		return true;
	}

}