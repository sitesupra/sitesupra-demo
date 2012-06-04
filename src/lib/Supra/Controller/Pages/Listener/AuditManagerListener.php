<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Supra\Controller\Pages\Exception\LogicException;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\Template;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Controller\Pages\Entity\Abstraction\Block;
use Supra\Controller\Pages\Entity;
use \ReflectionClass;
use Supra\Controller\Pages\Entity\Abstraction\AuditedEntityInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Supra\Controller\Pages\PageController;
use Doctrine\ORM\PersistentCollection;
use Supra\Database\Doctrine\Hydrator\ColumnHydrator;
use Supra\Controller\Pages\Event\AuditEvents;
use Supra\Controller\Pages\Event\SetAuditRevisionEventArgs;

/**
 * Makes sure no manual changes are performed
 */
class AuditManagerListener implements EventSubscriber
{
	/**
	 * @var string
	 */
	private $revision;
	
	/**
	 * Used to determine when second loading phase from the draft manager can be started
	 * @var boolean
	 */
	private $firstObjectLoadState = true;

	/**
	 * @var \Supra\Log\Writer\WriterAbstraction
	 */
	private $log;
	
	/**
	 * Used for nice debug log output
	 * @var int
	 */
	private $depth = 0;

	public function __construct()
	{
		$this->log = ObjectRepository::getLogger($this);
	}
	
	/**
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(
			// set audit revision to work with
			AuditEvents::setAuditRevision,
			// block manual inserts, updates
			Events::onFlush,
			// replaces associations with supraId20 column
			Events::loadClassMetadata,
			// collects correct associations from audit schema
			Events::postLoad,
		);
	}
	
	/**
	 * @param string $revision
	 */
	public function setAuditRevision(SetAuditRevisionEventArgs $revision)
	{
		$this->revision = $revision->getRevision();
	}

	private function debug()
	{
		$args = func_get_args();
		$args[0] = str_repeat("    ", $this->depth) . $args[0];

		call_user_func_array(array($this->log, 'debug'), $args);
	}


	/**
	 * Just checks that nobody tries to write into the audit schema
	 * @param OnFlushEventArgs $eventArgs
	 * @throws LogicException
	 */
	public function onFlush(OnFlushEventArgs $eventArgs)
	{
		$uow = $eventArgs->getEntityManager()
				->getUnitOfWork();

		$scheduledInsertions = $uow->getScheduledEntityInsertions();
		$scheduledUpdates = $uow->getScheduledEntityUpdates();

		if (count($scheduledInsertions) > 0 || count($scheduledUpdates) > 0) {
			throw new LogicException('Audit EntityManager is read only. Only deletions are allowed');
		}
	}

	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$classMetadata = $eventArgs->getClassMetadata();

		$associationMappings = $classMetadata->associationMappings;
		$classMetadata->auditAssociationMappings = $associationMappings;
		$this->debug("Cleaning the associations for {$classMetadata->name}");

		foreach ($associationMappings as $field => $mapping) {

//			$targetClassName = $mapping['targetEntity'];
//
//			$targetReflection = new ReflectionClass($targetClassName);
//
//			if ($targetReflection->implementsInterface(AuditedEntityInterface::CN)) {
			
			// Kill ALL associations
			unset($classMetadata->associationMappings[$field]);

			// Still for *_to_one stuff we need to keep the column, but as simple string one
			if (($mapping['type'] & ClassMetadata::TO_ONE) && $mapping['isOwningSide']) {

				$column = null;

				if (count($mapping['joinColumns']) != 1) {
					$this->debug("HMM................. more than one join column ", $mapping);
				}

				if (isset($mapping['joinColumns'][0]['name'])) {
					$column = $mapping['joinColumns'][0]['name'];
				} else {
					$this->debug("HMM................. no name ", $mapping);
				}

				$fieldMap = array(
					'fieldName' => $field,
					'type' => 'supraId20',
					'length' => null,
					'precision' => 0,
					'scale' => 0,
					'nullable' => true,
					'unique' => false,
					'id' => false,
					'columnName' => $column,
				);

				$classMetadata->mapField($fieldMap);
			}
		}
	}
	
	/**
	 * Should collect associations from the audit schema by revision
	 * @param LifecycleEventArgs $eventArgs
	 * @throws \Exception
	 */
	public function postLoad(LifecycleEventArgs $eventArgs)
	{
		$this->depth ++;

		$entityManager = $eventArgs->getEntityManager();
		$entity = $eventArgs->getEntity();

		$className = $entity::CN();
		$thisIsRootEntity = false;

		$this->debug("ENTER $className");

		// Let's fill the revision in case it is not set yet. Let's assume this is a root entity
		if ($this->firstObjectLoadState) {

			if ($entity instanceof AuditedEntityInterface) {

				$this->debug("ROOT $className");

				if (empty($this->revision)) {
					$this->revision = $entity->getRevisionId();
				}
				
				$thisIsRootEntity = true;
				$this->firstObjectLoadState = false;
			} else {

				$this->debug("SKIP $className (not audited)");

				$this->depth --;

				return;
			}
		}

		$classMetadata = $entityManager->getClassMetadata($className);

		// Let's find all association mappings we have backed up before
		$auditMappings = $classMetadata->auditAssociationMappings;

		if ( ! $classMetadata->isInheritanceTypeNone()) {
			$rootEntity = $classMetadata->rootEntityName;
			$rootMetaData = $entityManager->getClassMetadata($rootEntity);

			$auditMappings = array_merge($auditMappings, $rootMetaData->auditAssociationMappings);
		}

		// Let's crawl, one_to_* associations beforehand
		foreach ($auditMappings as $field => $mapping) {

			// Check if isn't loaded already
			$this->loadOneToAnything($entityManager, $mapping, $className, $field, $entity, $classMetadata);
		}

//		// The middle phase incase we don't have localization as root node
//		if ($thisIsRootEntity && ( ! $entity instanceof Entity\Abstraction\AbstractPage && ! $entity instanceof Entity\Abstraction\Localization)) {
//			$this->loadAuditRelatedToOne($entityManager, $entity);
//		}
		
		if ($thisIsRootEntity) {
			// Start the second phase when everything is done
			$this->postLoadSecondPhase($entityManager, $entity);
			
			// Reset the revision ID memory so the next request doesn't fail
			$this->debug("END. Resetting the revision, our job is done here, I hope");
			$this->revision = null;
			$this->firstObjectLoadState = true;
		}

		$this->depth --;
	}

	private function loadOneToAnything(\Doctrine\ORM\EntityManager $entityManager, $mapping, $className, $field, $entity, $classMetadata)
	{
		$value = null;
		$targetEntityName = $mapping['targetEntity'];
		$targetEntityClass = new ReflectionClass($targetEntityName);

		$this->debug(sprintf("1ST %-20s %-20s %-20s", $className, $field, $targetEntityName));

		$this->depth ++;

		$reflField = new \ReflectionProperty($className, $field);
		$reflField->setAccessible(true);
		$currentValue = $reflField->getValue($entity);

		if (is_object($currentValue)) {

			$this->debug("SKIP, object already.. ", get_class($currentValue));
			$this->depth --;

			return;
		}

		// We are interested in records inside the audit for now
		if ($targetEntityClass->implementsInterface(AuditedEntityInterface::CN)) {

			$targetMetadata = $entityManager->getClassMetadata($targetEntityName);
			switch ($mapping['type']) {
				
				// Case for asociation block property metadata -> referenced element
				// TODO: check if no other links outside the audit scope!!!
				case ClassMetadata::ONE_TO_ONE:

					if ($mapping['isOwningSide']) {
						$this->debug(sprintf("1-1 %-20s %-20s", $className, $targetEntityName));
						
						// No value, needs to be string
						if (is_null($currentValue)) {
							break;
						}
						
						$value = $this->loadOneToOneAuditted($entityManager, $currentValue, $targetMetadata);
					}
					
					break;

				case ClassMetadata::ONE_TO_MANY:

					$this->debug("LOAD $className 1_+ $targetEntityName association");

					$records = $this->loadOneToManyAuditted($entityManager, $entity, $mapping, $targetMetadata);

					// Fill the other association side (the owning side)
					$targetReflField = new \ReflectionProperty($targetEntityName, $mapping['mappedBy']);
					$targetReflField->setAccessible(true);

					foreach ($records as $record) {
						$targetReflField->setValue($record, $entity);
					}

					// Create the collection
					$value = new ArrayCollection($records);

					break;
				default:
					$this->debug("SKIP till 2ND");
			}
		} else {
			$this->debug("SKIP (not auditted)");
		}

		// Set the stuff we have found.. if we have found it
		if ( ! is_null($value)) {
			$reflField->setValue($entity, $value);
		}

		$this->depth --;
	}

	/**
	 * @param \Doctrine\ORM\EntityManager $entityManager
	 * @param string $currentValue
	 * @param \Doctrine\ORM\Mapping\ClassMetadata $targetMetadata
	 * @return Entity\Abstraction\Entity
	 */
	private function loadOneToOneAuditted(\Doctrine\ORM\EntityManager $entityManager, $currentValue, \Doctrine\ORM\Mapping\ClassMetadata $targetMetadata)
	{
		if (is_null($currentValue)) {
			$this->debug("NULL in field");
			
			return null;
		}
		
		// First of all we need to read 2 column data
		$qb = $entityManager->createQueryBuilder();
		$qb->from($targetMetadata->name, 'e')
				// By ID
				->andWhere('e.id = :id')
				->setParameter('id', $currentValue)
				// Get max revision till the mentioned revision
				->select('MAX(e.revision) AS revision')
				->andWhere('e.revision <= :revision')
				->setParameter('revision', $this->revision)
				;

		$revision = $qb->getQuery()
				->getOneOrNullResult(ColumnHydrator::HYDRATOR_ID);

		if (is_null($revision)) {
			$this->debug("NO RESULT");

			return null;
		}

		$this->debug("REVISION ", $revision);
		
		// Load real data now
		$qb = $entityManager->createQueryBuilder();
		$qb->from($targetMetadata->name, 'e')
				->select('e')
				// By ID
				->andWhere('e.id = :id')
				->setParameter('id', $currentValue)
				// By revision
				->andWhere('e.revision = :revision')
				->setParameter('revision', $revision)
				// Skip if "removal" revision
				->andWhere('e.revisionType != :revisionType')
				->setParameter('revisionType', EntityAuditListener::REVISION_TYPE_DELETE)
				;

		$record = $qb->getQuery()->getOneOrNullResult();
		
		if (is_null($record)) {
			$this->debug("NOT FOUNT, seems to be DELETE revision");
		}
		
		return $record;
	}
	
	/**
	 * @param \Doctrine\ORM\EntityManager $entityManager
	 * @param \Supra\Database\Entity $entity
	 * @param array $mapping
	 * @param \Doctrine\ORM\Mapping\ClassMetadata $targetMetadata
	 * @return array
	 */
	private function loadOneToManyAuditted(\Doctrine\ORM\EntityManager $entityManager, \Supra\Database\Entity $entity, array $mapping, \Doctrine\ORM\Mapping\ClassMetadata $targetMetadata)
	{
		// First of all we need to read 2 column data
		$qb = $entityManager->createQueryBuilder();
		$qb->from($targetMetadata->name, 'e')
				->select('e.id, MAX(e.revision) AS revision')
				->where('e.revision <= :revision')
				->setParameter('revision', $this->revision)
				->groupBy('e.id')
				->andWhere("e.{$mapping['mappedBy']} = :parentId")
				->setParameter('parentId', $entity->getId());

		$records = $qb->getQuery()
				->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

		if (empty($records)) {
			$this->debug("NO RESULTS");

			return array();
		}

		$this->debug("RESULTS ", count($records));
		
		$indexBy = null;
		
		// @indexBy annotation
		if (isset($mapping['indexBy'])) {
			$indexBy = 'e.' . $mapping['indexBy'];
		}
		
		// Load real data now
		$qb = $entityManager->createQueryBuilder();
		$qb->from($targetMetadata->name, 'e', $indexBy)
				->select('e')
				->where('e.revisionType != :revisionType')
				->setParameter('revisionType', EntityAuditListener::REVISION_TYPE_DELETE);

		$or = $qb->expr()->orX();
		$qb->andWhere($or);
		
		foreach ($records as $i => $record) {
			$or->add("e.id = :id_$i AND e.revision = :revision_$i");
			$qb->setParameters(array(
						'id_' . $i => $record['id'],
						'revision_' . $i => $record['revision'],
					));
		}
		
		// @OrderBy annotation
		if (isset($mapping['orderBy'])) {
			foreach ($mapping['orderBy'] as $sort => $order) {
				$qb->addOrderBy('e.' . $sort, $order);
			}
		}
		
		$records = $qb->getQuery()->getResult();
		
		return $records;
	}
	
//	private function loadAuditRelatedToOne(\Doctrine\ORM\EntityManager $entityManager, \Supra\Database\Entity $entity)
//	{
//		$className = $entity::CN();
//		$this->debug("ENTER $className");
//
//		$this->depth ++;
//
//		$classMetadata = $entityManager->getClassMetadata($className);
//
//		// Let's find all association mappings we have backed up before
//		$auditMappings = $classMetadata->auditAssociationMappings;
//
//		if ( ! $classMetadata->isInheritanceTypeNone()) {
//			$rootEntity = $classMetadata->rootEntityName;
//			$rootMetaData = $entityManager->getClassMetadata($rootEntity);
//
//			$auditMappings = array_merge($auditMappings, $rootMetaData->auditAssociationMappings);
//		}
//
//
//		foreach ($auditMappings as $field => $mapping) {
//
//			$this->debug("ASSOC $field");
//
//			$this->depth ++;
//
//			$reflField = new \ReflectionProperty($className, $field);
//			$reflField->setAccessible(true);
//			$value = $reflField->getValue($entity);
//			
//			$targetEntity = $mapping['targetEntity'];
//
//			// Here we need to do anything only if "to_one" association is found, owning side
//			if (($mapping['type'] & ClassMetadata::TO_ONE) && $mapping['isOwningSide']) {
//
////				if ($className)
//				
//				$pair = array($className, $targetEntity);
//				
//				$allowedPairs = array(
//					array(BlockProperty::CN(), Entity\Abstraction\Localization::CN()),
//					
//					array(Entity\PageBlock::CN(), Entity\Abstraction\PlaceHolder::CN()),
//					array(Entity\TemplateBlock::CN(), Entity\Abstraction\PlaceHolder::CN()),
//					
//					array(Entity\PagePlaceHolder::CN(), Entity\Abstraction\Localization::CN()),
//					array(Entity\TemplatePlaceHolder::CN(), Entity\Abstraction\Localization::CN()),
//					
//					array(Entity\BlockPropertyMetadata::CN(), BlockProperty::CN()),
//				);
//				
//				$notAllowedPairs = array(
//					array(BlockProperty::CN(), Block::CN()),
//					
//					array(PageLocalization::CN(), Entity\Abstraction\AbstractPage::CN()),
//					array(Entity\TemplateLocalization::CN(), Entity\Abstraction\AbstractPage::CN()),
//					array(Entity\ApplicationLocalization::CN(), Entity\Abstraction\AbstractPage::CN()),
//				);
//				
//				if ( ! in_array($pair, $allowedPairs)) {
//					if ( ! in_array($pair, $notAllowedPairs)) {
//						$this->debug("UNDOC!!! Pair $className --> $targetEntity");
//					}
//					
//					continue;
//				}
//				
//				// Will load if the value is string
//				if (is_null($value)) {
//					
//				} elseif (is_string($value)) {
//
//					$this->debug("WILL LOAD");
//
//					$targetMetadata = $entityManager->getClassMetadata($targetEntity);
//					$loadedValue = $this->loadOneToOneAuditted($entityManager, $value, $targetMetadata);
//					
//					if (empty($loadedValue)) {
//						
//						if ($targetEntity != Entity\LockData::CN()) {
//							$this->debug("$className #{$entity->getId()}, rev. {$entity->getRevisionId()} => {$targetEntity}");
//							throw new \RuntimeException("OOPS in $className -> {$targetEntity}");
//						}
//					}
//					
//					$reflField->setValue($entity, $loadedValue);
//					
//				} else {
//					$this->debug("LOADED or null");
//				}
//
//			} else {
//				// For one_to_many just recurse deeper
//				if ($mapping['type'] == ClassMetadata::ONE_TO_MANY) {
//
//					if ( ! is_null($value)) {
//
//						$this->debug("DEEP");
//
//						// TODO: Shouldn't we remember the visited places to avoid loops?
//						foreach ($value as $record) {
//							$this->loadAuditRelatedToOne($entityManager, $record);
//						}
//
//					} else {
//						$this->debug("NULL");
//					}
//
//				}
//
//			}
//
//			$this->depth--;
//		}
//
//		$this->depth--;
//	}

	/**
	 * Start the second phase, reading the missing information from the draft entity manager
	 * @param \Doctrine\ORM\EntityManager $entityManager
	 * @param \Supra\Database\Entity $entity
	 */
	private function postLoadSecondPhase(\Doctrine\ORM\EntityManager $entityManager, \Supra\Database\Entity $entity)
	{
		$className = $entity::CN();
		$this->debug("ENTER $className");

		$this->depth ++;

		$classMetadata = $entityManager->getClassMetadata($className);

		// Let's find all association mappings we have backed up before
		$auditMappings = $classMetadata->auditAssociationMappings;

		if ( ! $classMetadata->isInheritanceTypeNone()) {
			$rootEntity = $classMetadata->rootEntityName;
			$rootMetaData = $entityManager->getClassMetadata($rootEntity);

			$auditMappings = array_merge($auditMappings, $rootMetaData->auditAssociationMappings);
		}


		foreach ($auditMappings as $field => $mapping) {

			$this->debug("ASSOC $field");

			$this->depth ++;

			$reflField = new \ReflectionProperty($className, $field);
			$reflField->setAccessible(true);
			$value = $reflField->getValue($entity);

			// Here we need to do anything only if "to_one" association is found, owning side
			if (($mapping['type'] & ClassMetadata::TO_ONE) && $mapping['isOwningSide']) {

				// Will load if the value is string
				if (is_null($value)) {
					
				} elseif (is_string($value)) {

					$this->debug("WILL LOAD");
					$targetEntity = $mapping['targetEntity'];

					$draftEm = ObjectRepository::getEntityManager(PageController::SCHEMA_DRAFT);
					$loadedValue = $draftEm->find($targetEntity, $value);
					
					if (empty($loadedValue)) {
						
						if ($targetEntity != Entity\LockData::CN()) {
							$this->debug("$className #{$entity->getId()}, rev. {$entity->getRevisionId()} => {$targetEntity}");
							throw new \RuntimeException("OOPS in $className -> {$targetEntity}");
						}
					}
					
					$reflField->setValue($entity, $loadedValue);
					
				} else {
					$this->debug("LOADED or null");
				}

			} else {
				// For one_to_many just recurse deeper
				if ($mapping['type'] == ClassMetadata::ONE_TO_MANY) {

					if ( ! is_null($value)) {

						$this->debug("DEEP");

						// TODO: Shouldn't we remember the visited places to avoid loops?
						foreach ($value as $record) {
							$this->postLoadSecondPhase($entityManager, $record);
						}

					} else {
						$this->debug("NULL");
					}

				}

			}

			$this->depth--;
		}

		$this->depth--;
	}

}
