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
		
		// TODO: temporary, should make another solution
		$userProvider = ObjectRepository::getUserProvider($this, false);
		if ($userProvider instanceof \Supra\User\UserProviderAbstract) {
			$this->user = $userProvider->getSignedInUser();
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
					|| $fieldName == 'revision') {
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
		if ( ! $entity instanceof AuditedEntityInterface) {
			return;
		}
		
		$class = $this->auditEm->getClassMetadata(get_class($entity));
		
		$originalEntityData = $this->uow->getOriginalEntityData($entity);
		
		$this->saveRevisionEntityData($class, $originalEntityData, $revisionType);
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
			$types[] = \PDO::PARAM_STR;
			
			if ($revisionType == self::REVISION_TYPE_DELETE) {
				
				if ( ! isset($this->revision)) {
									
					$revision = new PageRevisionData();
					$revision->setElementName($class->name);
					$revision->setElementId($entityData['id']);

					$revision->setType(PageRevisionData::TYPE_REMOVED);
					$revision->setReferenceId($this->referenceId);

					$revision->setUser($this->getCurrentUserId());
	
					$em = ObjectRepository::getEntityManager('#public');

					$em->persist($revision);
					$em->flush();
					
					$this->revision = $revision;
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
	public function pagePreDeleteEvent(PageDeleteEventArgs $eventArgs) 
	{
		$this->_pageDeleteState = true;
		$pageId = $eventArgs->getPageId();
		
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

		$this->createPageCopy($eventArgs);
		
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
	 * Take a full page snapshot inside audit tables under special revision with
	 * type "TYPE_HISTORY_RESTORE"
	 *  
	 * @param PageEventArgs $eventArgs
	 */
	public function pagePostRestoreEvent(PageEventArgs $eventArgs) 
	{
		//$this->prepareEnvironment($eventArgs);
		
		//$revisionData = $this->createRevisionData(PageRevisionData::TYPE_HISTORY_RESTORE);
					
		//$this->staticRevisionId = $revisionData->getId();
		
		//$this->createPageFullCopy();
		
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
	private function createRevisionData($type) 
	{
		$revisionData = new PageRevisionData();
		$revisionData->setUser($this->getCurrentUserId());
		$revisionData->setType($type);
		$revisionData->setReferenceId($this->referenceId);
		
		$this->em->persist($revisionData);
		$this->em->flush();
		
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
	
	private function createPageFullCopy()
	{
		$page = $this->em->find(Entity\Abstraction\AbstractPage::CN(), $this->referenceId);
		
		/* @var $page Entity\Abstraction\AbstractPage */
		if (is_null($page)) {
			throw new RuntimeException("Failed to find page by reference #{$this->referenceId}");
		}
		
		$pageLocalizations = $this->em->getRepository(Localization::CN())
				->findBy(array('master' => $page->getId()));
		
		foreach($pageLocalizations as $localization) {
			$this->referenceId = $localization->getId();
			$this->createPageCopy(true);
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
	
}