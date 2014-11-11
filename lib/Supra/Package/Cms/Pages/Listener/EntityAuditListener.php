<?php

namespace Supra\Package\Cms\Pages\Listener;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Supra\Controller\Pages\PageController;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\PersistentCollection;
use Supra\Controller\Pages\Event\AuditEvents;
use Supra\Controller\Pages\Event\PageEventArgs;
use Supra\Controller\Pages\Exception\LogicException;
use Supra\Controller\Pages\Exception\RuntimeException;
use Supra\Controller\Pages\Request\PageRequestEdit;


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
	
	/**
	 * Listener states, generally is used to skip audit writing
	 * when page is created/deleted/restored
	 */
	private $_pageDeleteState = false;
	private $_pageRestoreState = false;
	private $_pageCreateState = false;
	
	/**
	 * @var \Supra\User\Entity\User
	 */
	private $user;
	
	/**
	 * @var string
	 */
	private $referenceId;
	
	/**
	 * @var string
	 */
	private $globalElementReferenceId;
	
	/**
	 * @var PageRevisionData
	 */
	private $revision;
	
	
	/**
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
			
			AuditEvents::pagePreEditEvent,
			
			AuditEvents::pagePreCreateEvent,
			AuditEvents::pagePostCreateEvent,
			
			AuditEvents::pagePreDuplicateEvent,
			AuditEvents::pagePostDuplicateEvent,
			
			AuditEvents::localizationPreRestoreEvent,
			AuditEvents::localizationPostRestoreEvent,
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
		
//		$this->auditEm->getProxyFactory()
//				->getProxy(Entity\ReferencedElement\LinkReferencedElement::CN(), -1);
//		
//		$this->auditEm->getProxyFactory()
//				->getProxy(BlockProperty::CN(), -1);
		
		if ($eventArgs instanceof LifecycleEventArgs) {
			$this->em = $eventArgs->getEntityManager();
		} elseif ($eventArgs instanceof OnFlushEventArgs) {
			$this->em = $eventArgs->getEntityManager();
		} elseif ($eventArgs instanceof PagePublishEventArgs) {
			$this->em = $eventArgs->getEntityManager();
		} else if ($eventArgs instanceof PageEventArgs) {
			$this->em = $eventArgs->getEntityManager();
			$referenceId = $eventArgs->getProperty('referenceId');
			if ( ! empty($referenceId)) {
				$this->referenceId = $referenceId;
			}
		} else {
			throw new \LogicException("Unknown event args received");
		}
		
		$this->uow = $this->em->getUnitOfWork();
		$this->conn = $this->em->getConnection();
		$this->platform = $this->conn->getDatabasePlatform();
		
		
		if ( ! $this->user instanceof \Supra\User\Entity\User) {
			$this->loadCurrentUserInfo();
		}
	}
	
	/**
	 * Try to get info about current user
	 * TODO: make another solution
	 */
	protected function loadCurrentUserInfo()
	{
		$userProvider = ObjectRepository::getUserProvider($this, false);
		if ($userProvider instanceof \Supra\User\UserProviderAbstract) {
			$this->user = $userProvider->getSignedInUser();
			
			if (is_null($this->user)) {
				$this->user = new \Supra\User\SystemUser();
			}
		}
	}
	
	/**
	 * @param LifecycleEventArgs $eventArgs 
	 */
	public function postPersist(LifecycleEventArgs $eventArgs)
	{
		if ($this->isAuditSkipped()) {
			return;
		}
		
		$this->prepareEnvironment($eventArgs);
				
		$entity = $eventArgs->getEntity();
		
		$this->insertAuditRecord($entity, self::REVISION_TYPE_INSERT);
	}

	/**
	 * @param LifecycleEventArgs $eventArgs
	 */
	public function postUpdate(LifecycleEventArgs $eventArgs)
	{
		if ($this->isAuditSkipped()) {
			return;
		}
		
		// skip any update raised on page delete
		if ($this->_pageDeleteState) {
			return;
		}
		
		$this->prepareEnvironment($eventArgs);
		$entity = $eventArgs->getEntity();
		
		$changeSet = $this->uow->getEntityChangeSet($entity);
		foreach($changeSet as $fieldName => $fieldValue) {
			if ($fieldValue instanceof PersistentCollection
					|| ($entity instanceof Localization && $fieldName == 'lock')
//					|| $fieldName == 'revision'
					|| ($fieldValue[0] instanceof \DateTime && $fieldValue[0] == $fieldValue[1]))
			{
				unset($changeSet[$fieldName]);
			}
		}

		if ( ! empty($changeSet)) {
			$this->insertAuditRecord($entity, self::REVISION_TYPE_UPDATE);
		}
	}
	
	/**
	 * @param OnFlushEventArgs $eventArgs 
	 */
	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		if ($this->isAuditSkipped()) {
			return;
		}
		
		$this->prepareEnvironment($eventArgs);

		$visitedIds = array();
		foreach ($this->uow->getScheduledEntityDeletions() AS $entity) {
			
			$entityId = $entity->getId() . $entity::CN();
			if ( ! in_array($entityId, $visitedIds)) {
				
				$revisionType = self::REVISION_TYPE_DELETE;
				
				// Questionable
				if ($this->_pageDeleteState) {
					$revisionType = self::REVISION_TYPE_COPY;
				}
	
				$this->insertAuditRecord($entity, $revisionType);
				array_push($visitedIds, $entityId);
			}
		}
	}
	
	/**
	 * @param Entity $entity
	 * @param integer $revisionType
	 */
	private function insertAuditRecord($entity, $revisionType)
	{
		if ( ! $entity instanceof AuditedEntityInterface) {
			return;
		}
		
		if ($entity instanceof Entity\SharedBlockProperty) {
			return;
		}
		
		$class = $this->auditEm->getClassMetadata(get_class($entity));
		
		$originalEntityData = $this->uow->getOriginalEntityData($entity);
		
		$this->saveRevisionEntityData($class, $originalEntityData, $revisionType, $entity);
	}

	/**
	 * @param ClassMetadata $class
	 * @param array $fieldNames
	 * @return string 
	 */
	private function getInsertRevisionSQL(ClassMetadata $class, array $fieldNames, array $sqlNames)
	{
		$tableName = $class->table['name'];

		$sql = 'INSERT INTO ' . $tableName
				. ' (' . implode(', ', $fieldNames) . ')'
				. ' VALUES (:' . implode(', :', $sqlNames) . ')';

		return $sql;
	}

	/**
	 * @param ClassMetadata $class
	 * @param array $entityData
	 * @param string $revisionType
	 */
	private function saveRevisionEntityData(ClassMetadata $class, $entityData, $revisionType, $entity)
	{
		$names = $params = $types = array();
		
		$classFields = $class->fieldNames;
		
		// two special cases for revision id:
		//   - if we are creating full COPY of page (publish/trash), 
		//	   then we should use single revision id for all auditing entities
		//   - if entity is deleted, then we need to generate new revision id, or there
		//     will be primary-key collisions inside audit schema in cases when
		//     when entity will be restored and deleted again
		if ($revisionType == self::REVISION_TYPE_COPY || $revisionType == self::REVISION_TYPE_DELETE) {
			$names[] = AuditCreateSchemaListener::REVISION_COLUMN_NAME;
			$types[] = \PDO::PARAM_STR;
			
			if ($revisionType == self::REVISION_TYPE_DELETE) {
				
				if ( ! isset($this->revision)) {
					
					$revisionData = $this->createRevisionData(PageRevisionData::TYPE_ELEMENT_DELETE, false);
					
					$revisionData->setElementName($class->name);
					$revisionData->setElementId($entityData['id']);

					// TODO: not nice as this is the duplicate of EntityRevisionSetterListener::findBlockName();
					$blockName = $this->findBlockName($entity);
					if ( ! empty($blockName)) {
						$revisionData->setElementTitle($blockName);
					}
					
					$em = ObjectRepository::getEntityManager('#public');
					$em->persist($revisionData);
					$em->flush($revisionData);
					
					$this->revision = $revisionData;
				}
				
				$params[] = $this->revision->getId();
			} else {
				$params[] = $this->staticRevisionId;
			}
			
			unset($classFields[AuditCreateSchemaListener::REVISION_COLUMN_NAME]);
		}
		
		// recursively store parent also if entity is defined as not single-inherited
		if ($class->name != $class->rootEntityName 
				&& $class->inheritanceType != ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE) {
			
			$rootClass = $this->auditEm->getClassMetadata($class->rootEntityName);
			$rootClass->discriminatorValue = $class->discriminatorValue;
			$this->saveRevisionEntityData($rootClass, $entityData, $revisionType, $entity);
		}

		foreach ($classFields as $columnName => $field) {

			if ($class->inheritanceType != ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE 
					&& $class->isInheritedField($field)
					&& ! $class->isIdentifier($field)
					&& $columnName != AuditCreateSchemaListener::REVISION_COLUMN_NAME) {
				continue;
			}
			
			$param = null;
			$type = null;
			
			if (isset($entityData[$field])) {
				$param = $entityData[$field];
			}
			
			if (isset($class->fieldMappings[$field]['type'])) {
				$type = $class->fieldMappings[$field]['type'];
			}
			
			if ($columnName == AuditCreateSchemaListener::REVISION_TYPE_COLUMN_NAME) {
				$param = $revisionType;
				$type = \PDO::PARAM_INT;
			}
			// In audit schema to_one fields are string fields and objects are 
			// waken up on object load. Now need to convert back to string.
			elseif ($param instanceof Entity\Abstraction\Entity) {
				$param = $param->getId();
				$type = \PDO::PARAM_STR;
				
				//TODO: might check "owning side" and "to_one" stuff... or not?
			}
			
			$names[] = $columnName;
			$params[] = $param;
			$types[] = $type;
		}
		
//		foreach ($class->auditAssociationMappings AS $field => $assoc) {
//			if ($class->isSingleValuedAssociation($field) && $assoc['isOwningSide']) {
//				$targetClass = $this->em->getClassMetadata($assoc['targetEntity']);
//
//				// Has value
//				if ($entityData[$field] !== null) {
//					$relatedId = $this->uow->getEntityIdentifier($entityData[$field]); // Or simply $entityData[$field]->getId()
//
//					foreach ($assoc['sourceToTargetKeyColumns'] as $sourceColumn => $targetColumn) {
//						$names[] = $sourceColumn;
//						$params[] = $relatedId[$targetClass->getFieldName($targetColumn)];
//						$types[] = $targetClass->getTypeOfColumn($targetColumn);
//					}
//				
//				// Null
//				} else {
//					foreach ($assoc['sourceToTargetKeyColumns'] as $sourceColumn => $targetColumn) {
//						$names[] = $sourceColumn;
//						$params[] = null;
//						$types[] = \PDO::PARAM_STR;
//					}
//				}
//			}
//		}
		
		// Discriminator
		if ($class->inheritanceType == ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE
				|| ($class->inheritanceType == ClassMetadata::INHERITANCE_TYPE_JOINED 
					&& $class->name == $class->rootEntityName)) {
			$names[] = $class->discriminatorColumn['name'];
			$params[] = $class->discriminatorValue;
			$types[] = $class->discriminatorColumn['type'];
		}
		
		$sqlNames = $this->prepareParameterNames($names);
		
		$insertRevisionSql = $this->getInsertRevisionSQL($class, $names, $sqlNames);
		
		$namedParams = array_combine($sqlNames, $params);
		$namedTypes = array_combine($sqlNames, $types);
		
		$this->conn->executeUpdate($insertRevisionSql, $namedParams, $namedTypes);
	}
	
	/**
	 * 
	 */
	public function pagePostDeleteEvent() 
	{
		$this->_pageDeleteState = false;
		$this->staticRevisionId = null;
	}
	
	/**
	 * Prepare Audit listener for draft page delete event
	 */
	public function pagePreDeleteEvent(PageEventArgs $eventArgs) 
	{
		$this->loadCurrentUserInfo();
		
		$this->_pageDeleteState = true;
		$page = $eventArgs->getProperty('master');
		$pageId = $page->getId();
		
		$revisionData = new PageRevisionData();
		$revisionData->setUser($this->getCurrentUserId());
		$revisionData->setType(PageRevisionData::TYPE_TRASH);
		$revisionData->setReferenceId($pageId);
		
		$em = ObjectRepository::getEntityManager('#cms');
		$em->persist($revisionData);
		$em->flush();
		
		$this->staticRevisionId = $revisionData->getId();
		
	}
	
	/**
	 * Page pre-edit event is fired straight after PageManager loads actual
	 * page localization data, mainly is used, to get ID of localization,
	 * which is currently edited by user
	 * 
	 * @param PageEventArgs $eventArgs
	 */
	public function pagePreEditEvent(PageEventArgs $eventArgs)
	{
		$this->referenceId = $eventArgs->getProperty('referenceId');
		$this->globalElementReferenceId = $eventArgs->getProperty('globalElementReferenceId');
	}
	
	/**
	 * Page pre-create event is fired in the beginning of page create/duplicate action.
	 * It sets listener in "pageCreateState", to skip any audit record 
	 * related with page element adding. Full page copy will be created later,
	 * inside pagePostCreateEvent method
	 *
	 */
	public function pagePreCreateEvent()
	{
		$this->_pageCreateState = true;
	}
	
	/**
	 * See page pagePreCreateEvent doc
	 */
	public function pagePreDuplicateEvent()
	{
		$this->_pageCreateState = true;
	}
		
	/**
	 * Page pre-restore event is similar to page pre-create event, see doc for
	 * pagePreCreateEvent() method
	 */
	public function pagePreRestoreEvent() 
	{
		$this->_pageRestoreState = true;
	}
	
	/**
	 * 
	 */
	public function localizationPreRestoreEvent() 
	{
		$this->pagePreRestoreEvent();
	}
	
	/**
	 * Take a full page snapshot inside audit tables under special revision with
	 * type "TYPE_CREATE"
	 *  
	 * @param PageEventArgs $eventArgs
	 */
	public function pagePostCreateEvent(PageEventArgs $eventArgs)
	{
		$this->prepareEnvironment($eventArgs);
		
		$revisionData = $this->createRevisionData(PageRevisionData::TYPE_CREATE);
		
		$this->staticRevisionId = $revisionData->getId();

		$this->createPageCopy();
		
		$this->_pageCreateState = false;
		
	}
	
	/**
	 * Take a full page snapshot inside audit tables under special revision with
	 * type "TYPE_DUPLICATE"
	 *  
	 * @param PageEventArgs $eventArgs
	 */
	public function pagePostDuplicateEvent(PageEventArgs $eventArgs)
	{
		$this->prepareEnvironment($eventArgs);
		
		$revisionData = $this->createRevisionData(PageRevisionData::TYPE_DUPLICATE);
		
		$this->staticRevisionId = $revisionData->getId();

		$this->createPageCopy();
		
		$this->_pageCreateState = false;
		
	}
	
	/**
	 * @param PageEventArgs $eventArgs
	 */
	public function pagePostRestoreEvent() 
	{
		$this->_pageRestoreState = false;
	}
	
	/**
	 * Take a partial page snapshot (single localization, specified by reference and master data)
	 * inside audit tables under special revision with
	 * type "TYPE_CREATE"
	 *  
	 * @param PageEventArgs $eventArgs
	 */
	public function localizationPostRestoreEvent(PageEventArgs $eventArgs)
	{
		$this->prepareEnvironment($eventArgs);
		
		$revisionData = $this->createRevisionData(PageRevisionData::TYPE_HISTORY_RESTORE);
		
		$this->staticRevisionId = $revisionData->getId();

		$this->createPageCopy();
		
		$this->_pageCreateState = false;
		
	}
		
	/**
	 * Take a full page snapshot inside audit tables under special revision with
	 * type "TYPE_HISTORY"
	 *  
	 * @param PageEventArgs $eventArgs
	 */
	public function pagePublishEvent(PageEventArgs $eventArgs) 
	{
		$this->prepareEnvironment($eventArgs);
		
		$revisionData = $this->createRevisionData(PageRevisionData::TYPE_HISTORY);
		
		$this->staticRevisionId = $revisionData->getId();

		$this->createPageCopy();
	}
	
	/**
	 * Helper method, to detect, is it necessary to skip audit record insertion,
	 * based on listener states
	 * 
	 * @return boolean
	 */
	private function isAuditSkipped()
	{
		if ($this->_pageCreateState || $this->_pageRestoreState) {
			return true;
		}
			
		return false;
	}
	
	/**
	 * Helper method, to create page revision data object, with specified type
	 * 
	 * @param string $type
	 */
	private function createRevisionData($type, $store = true) 
	{
		$revisionData = new PageRevisionData();
		$revisionData->setUser($this->getCurrentUserId());
		$revisionData->setType($type);
		$revisionData->setReferenceId($this->referenceId);
		
		if ( ! empty($this->globalElementReferenceId)) {
			$revisionData->setGlobalElementReferenceId($this->globalElementReferenceId);
		}
		
		if ($store) {
			$this->em->persist($revisionData);
			$this->em->flush($revisionData);
		}
			
		return $revisionData;
	}
	
	/**
	 * Helper method, to return user id of user that raised audit event
	 * Mainly is used as property inside PageRevisionData
	 * 
	 * @return string
	 */
	private function getCurrentUserId()
	{
		$userId = null;
		if ($this->user instanceof \Supra\User\Entity\User) {
			$userId = $this->user->getId();
		}
		
		return $userId;
	}
	
	/**
	 * Helper method, which creates audit records for entire page
	 * All page element copies are created inside audit tables under single revision ID
	 * 
	 */
	private function createPageCopy($skipMaster = false)
	{
		if (is_null($this->referenceId)) {
			throw new RuntimeException('Reference Id is not defined');
		}
		
		$localization = $this->em->find(Localization::CN(), $this->referenceId);
		if (is_null($localization)) {
			throw new RuntimeException("Failed to find localization by reference #{$this->referenceId}");
		}
		
		if ($localization instanceof PageLocalization) {
			$localization->initializeProxyAssociations();
		}
		
		$this->insertAuditRecord($localization, self::REVISION_TYPE_COPY);
		
		// page localization redirect
		if ($localization instanceof PageLocalization) {
			$redirect = $localization->getRedirect();
			if ( ! is_null($redirect)) {
				$this->insertAuditRecord($redirect, self::REVISION_TYPE_COPY);
			}
		}
		
		// page itself
		if ( ! $skipMaster) {
			$page = $localization->getMaster();
			$this->insertAuditRecord($page, self::REVISION_TYPE_COPY);

			// template layouts
			if ($page instanceof Entity\Template) {
				$layouts = $page->getTemplateLayouts();

				foreach($layouts as $layout) {
					$this->insertAuditRecord($layout, self::REVISION_TYPE_COPY);
				}
			}
		}
		
		// page placeholders
		$placeHolders = $localization->getPlaceHolders();
		foreach ($placeHolders as $placeHolder) {
			$this->insertAuditRecord($placeHolder, self::REVISION_TYPE_COPY);
		}
		
		$request = PageRequestEdit::factory($localization);
		$request->setDoctrineEntityManager($this->em);
		$request->setLocale($localization->getLocale());
		
		$blockSet = $request->getBlockSet();
		foreach($blockSet as $block) {
			$this->insertAuditRecord($block, self::REVISION_TYPE_COPY);
		}
		
		$blockPropertySet = $request->getBlockPropertySet();
		foreach($blockPropertySet as $property) {
			
			if ($property instanceof Entity\SharedBlockProperty) {
				continue;
			}
			
			$this->insertAuditRecord($property, self::REVISION_TYPE_COPY);

			$metaData = $property->getMetadata();
			if ( ! empty($metaData)) {
				foreach($metaData as $metaDataItem) {
					$referencedElement = $metaDataItem->getReferencedElement();
					$this->insertAuditRecord($referencedElement, self::REVISION_TYPE_COPY);
					$this->insertAuditRecord($metaDataItem, self::REVISION_TYPE_COPY);
				}
			}
		}
	}
	
	/**
	 * Update up to 2.2 ORM caused wrong query params placeholder handling, 
	 * see SQLParserUtils::getPlaceholderPositions (preg_match is wrong?)
	 * this method is a workaround
	 * @param array $names
	 * @return array
	 */
	private function prepareParameterNames($names)
	{
		foreach($names as &$name) {
			if (strstr($name, '_')) {
				list($before, $after) = explode('_', $name);
				$name = $before . ucfirst($after);
			}
		}
	
		return $names;
	}
	
	/**
	 * @param \Supra\Database\Entity $entity
	 * @return string
	 */
	private function findBlockName($entity)
	{
		$block = null;
		
		switch (true) {
			case ($entity instanceof Entity\BlockPropertyMetadata):
				$block = $entity->getBlockProperty()
						->getBlock();
				break;
			
			case ($entity instanceof Entity\BlockProperty):
				$block = $entity->getBlock();
				break;
			
			case ($entity instanceof Block):
				$block = $entity;
				break;
		}
		
		if ($block === null) {
			return;
		}
		
		$blockName = null;
		$entity = null;
		
		if ( ! is_null($block)) {
			$componentClass = $block->getComponentClass();
			$componentConfiguration = ObjectRepository::getComponentConfiguration($componentClass);

			if ($componentConfiguration instanceof \Supra\Controller\Pages\Configuration\BlockControllerConfiguration) {
				$blockName = $componentConfiguration->title;
			}
		}
		
		return $blockName;
	}
}