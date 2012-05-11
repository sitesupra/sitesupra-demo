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
	 * @var \Supra\Log\Writer\WriterAbstraction
	 */
	private $log;
	private $depth = 0;

	public function __construct()
	{
		$this->log = ObjectRepository::getLogger($this);
	}

	private function debug()
	{
		$args = func_get_args();
		$args[0] = str_repeat("    ", $this->depth) . $args[0];

		call_user_func_array(array($this->log, 'debug'), $args);
	}

	public function getSubscribedEvents()
	{
		return array(
			Events::onFlush,
			Events::postLoad,
			Events::loadClassMetadata,
		);
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

	/**
	 * Manually pre-load missing associated entities from draft schema
	 * @TODO: avoid using of hardcoded values
	 */
	public function classicPostLoad(LifecycleEventArgs $eventArgs)
	{
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		$draftEm = ObjectRepository::getEntityManager('#cms');

		if ($entity instanceof PageLocalization) {
			$entityOriginalData = $em->getUnitOfWork()->getOriginalEntityData($entity);

			$draftTemplate = $draftEm->find(Template::CN(), $entityOriginalData['template']);
			if ( ! is_null($draftTemplate)) {
				$entity->setTemplate($draftTemplate);
			} else {
				$entity->setNullTemplate();
			}

			// PageLocalization loaded from Audit schema could contain null path id
			// or id of unexisting path, which will cause EntityNotFoundException
			// if someone will try to get localization path
			// To avoid that, we will load path entity from actual localization
			// 
			// @TODO: generate new path entity using audit localization pathPart and
			// and parents draft pathes
			$id = $entity->getId();
			$draftLocalization = $draftEm->find(PageLocalization::CN(), $id);
			if ( ! is_null($draftLocalization)) {
				$draftPath = $draftLocalization->getPathEntity();

				$em->detach($entity);
				$entity->setPathEntity($draftPath);
			} else {
				$entity->resetPath();
			}
		} else if ($entity instanceof BlockProperty) {
			$entityOriginalData = $em->getUnitOfWork()->getOriginalEntityData($entity);

			$block = $entity->getBlock();
			if (is_null($block)) {
				$draftBlock = $draftEm->find(Block::CN(), $entityOriginalData['block_id']);
				if ( ! is_null($draftBlock)) {
					$entity->setBlock($draftBlock);
				}
			}
		}
	}

	/* TEST */

	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$classMetadata = $eventArgs->getClassMetadata();

		$associationMappings = $classMetadata->associationMappings;
		$classMetadata->auditAssociationMappings = $associationMappings;
		ObjectRepository::getLogger($this)
				->debug("Cleaning the associations for {$classMetadata->name}");

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
//			}
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
		if (empty($this->revision)) {

			//TODO: might be removed, for strict testing only for now
			if ( ! $entity instanceof PageLocalization) {
				$this->debug("OOPS $className");
				throw new \RuntimeException("Strict dev validation failed");
			}

			if ($entity instanceof AuditedEntityInterface) {

				$this->debug("ROOT $className");

				$this->revision = $entity->getRevisionId();
				$thisIsRootEntity = true;
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
			$this->loadOneToAnything($entityManager, $mapping, $className, $field, $targetMetadata, $entity, $class);
		}

		// Here we should have all ONE_TO_* associations ready from audit entity manager
		//....
		


		// Finally reset the revision ID memory so the next request doesn't fail hard
		if ($thisIsRootEntity) {
			$this->debug("END. Resetting the revision, our job is done here, I hope");
			$this->revision = null;

			// Start the second phase when everything is done
			$this->postLoadSecondPhase($entityManager, $entity);
		}

		$this->depth --;
	}

	private function loadOneToAnything(\Doctrine\ORM\EntityManager $entityManager, $mapping, $className, $field, $targetMetadata, $entity, $class)
	{
		$value = null;
		$targetEntityName = $mapping['targetEntity'];
		$targetEntityClass = new ReflectionClass($targetEntityName);

		$this->debug(sprintf("1ST %-20s %-20s %-20s", $className, $field, $targetEntityName));

		$this->depth ++;

		$reflField = new \ReflectionProperty($className, $field);
		$reflField->setAccessible(true);
		$currentValue = $reflField->getValue($entity);

		if ($currentValue !== null && is_object($currentValue)) {

			$this->debug("SKIP, not null anymore.. ", get_class($currentValue));
			$this->depth --;

			return;
		}

		// We are interested in records inside the audit for now
		if ($targetEntityClass->implementsInterface(AuditedEntityInterface::CN)) {

			$targetMetadata = $entityManager->getClassMetadata($targetEntityName);
			switch ($mapping['type']) {
				case ClassMetadata::ONE_TO_ONE:

					//TODO: remove later, will solve "owner side" problem later

					$this->debug(sprintf("TODO 1-1 %-20s %-20s", $class, $targetEntityName));

					break;

				case ClassMetadata::ONE_TO_MANY:

					$this->debug("LOAD $class 1_+ $targetEntityName association");

					$records = $this->loadOneToManyAuditted($entityManager, $entity, $mapping, $targetMetadata);

					// Fill the other association side
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
		
		// Load real data now
		$qb = $entityManager->createQueryBuilder();
		$qb->from($targetMetadata->name, 'e')
				->select('e');

		foreach ($records as $i => $record) {
			$qb->orWhere("e.id = :id_$i AND e.revision = :revision_$i")
					->setParameters(array(
						'id_' . $i => $record['id'],
						'revision_' . $i => $record['revision'],
					));
		}

		$records = $qb->getQuery()->getResult();

		return $records;
	}


	/**
	 * Start the second phase, reading the missing information from the draft entity manager
	 * @param \Doctrine\ORM\EntityManager $entityManager
	 * @param \Supra\Database\Entity $entity
	 */
	private function postLoadSecondPhase(\Doctrine\ORM\EntityManager $entityManager, \Supra\Database\Entity $entity)
	{
		$className = $entity::CN();
		$this->debug("ENTER $className");

		$this->depth++;

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

			$this->depth++;

			$reflField = new \ReflectionProperty($className, $field);
			$reflField->setAccessible(true);
			$value = $reflField->getValue($entity);

			// Here we need to do anything only if "to_one" association is found, owning side
			if (($mapping['type'] & ClassMetadata::TO_ONE) && $mapping['isOwningSide']) {

				// Will load if the value is string
				if (is_string($value)) {

					$this->debug("WILL LOAD");

					$draftEm = ObjectRepository::getEntityManager(PageController::SCHEMA_DRAFT);
				} else {
					$this->debug("LOADED");
				}

			} else {
				// For one_to_many just recurse deaper
				if ($mapping['type'] == ClassMetadata::ONE_TO_MANY) {

					if ( ! is_null($value)) {

						$this->debug("DEEP");

						// Shouldn't we remember the visited places?
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

	private function loadManyToOne(\Doctrine\ORM\EntityManager $entityManager, $mapping, $className, $field, $targetMetadata, $entity, $class)
	{
		$value = null;
		$targetEntityName = $mapping['targetEntity'];
		$targetEntityClass = new ReflectionClass($targetEntityName);

		$this->debug(sprintf("2ND %-20s %-20s %-20s", $className, $field, $targetEntityName));

		$this->depth ++;

		$reflField = new \ReflectionProperty($className, $field);
		$reflField->setAccessible(true);
		$currentValue = $reflField->getValue($entity);

		$pointsToAudit = $targetEntityClass->implementsInterface(AuditedEntityInterface::CN);

		$targetMetadata = $entityManager->getClassMetadata($targetEntityName);
		switch ($mapping['type']) {
			case ClassMetadata::ONE_TO_ONE:

				//TODO: remove later, will solve "owner side" problem later

				$this->debug(sprintf("TODO 1-1 %-20s %-20s", $class, $targetEntityName));

				break;

			case ClassMetadata::ONE_TO_MANY:

				$this->debug("TRAVERSE $class 1_+ $targetEntityName association");

				if ( ! is_null($currentValue)) {
					foreach ($currentValue as $record) {
						
					}
				}

				// Fill the other association side
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

				case ClassMetadata::MANY_TO_ONE:

					if ($currentValue !== null && is_object($currentValue)) {

						$this->debug("SKIP, not null anymore.. ", get_class($currentValue));
						$this->depth --;

						return;
					}

					$originalData = $entityManager->getUnitOfWork()
						->getOriginalEntityData($entity);

					$targetValue = null;
					if ($mapping['isOwningSide']) {
						$targetValue = $originalData[$field];
						$value = $this->loadToOneEntity($entity, $targetEntityName, 'id', $targetMetadata, $targetValue);
					}

					$value = ( empty($value) ? null : $value);

					break;

				default:
					throw new \Exception('Unknown mapping type', $mapping['type']);
		}

		// Set the stuff we have found.. if we have found it
		if ( ! is_null($value)) {
			$reflField->setValue($entity, $value);
		}

		$this->depth --;
	}

//	private function loadToManyCollection($entity, $targetClassName, $targetColumn, $targetMetadata)
//	{
//		$em = ObjectRepository::getEntityManager(PageController::SCHEMA_AUDIT);
//		$revision = $entity->getRevisionId();
//
//		$entityId = $entity->getId();
//
//		$map = $targetMetadata->auditAssociationMappings[$targetColumn];
//		$tgCol = $map['fieldName'];
//
//		$qb = $em->createQueryBuilder();
//		$qb->select('max(e.revision) as revision, e.id')
//				->from($targetClassName, 'e')
//				->where('e.' . $tgCol . ' = ?0')
//				->andWhere('e.revision <= ?1')
//				->groupBy('e.revision')
//				->setParameters(array($entityId, $revision))
//		;
//
//		$query = $qb->getQuery();
//		$primaryKeys = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
//
//		$entities = null;
//		if ( ! empty($primaryKeys)) {
//			/*
//			  $qb = $em->createQueryBuilder();
//			  $expr = $qb->expr(); $or = $expr->orX(); $i = 0;
//
//			  foreach($primaryKeys as $keys) {
//
//			  list($revision, $id) = array_values($keys);
//
//			  $and = $expr->andX();
//			  $and->add($expr->eq('e.id', '?' . (++$i)));
//			  $qb->setParameter($i, $id);
//			  $and->add($expr->gte('e.revision', '?' . (++$i)));
//			  $qb->setParameter($i, $revision);
//			  $or->add($and);
//			  }
//
//			  $qb->select('e')
//			  ->from($targetClassName, 'e')
//			  ->where($or)
//			  ;
//
//			  $entities = $qb->getQuery()
//			  ->getResult();
//			 */
//
//			foreach ($primaryKeys as $keys) {
//				$entities[] = $em->getReference($targetClassName, $keys);
//			}
//		}
//
//		$arrayCollection = ( ! empty($entities) ? new ArrayCollection($entities) : new ArrayCollection);
//
//		$collection = new PersistentCollection($em, $targetMetadata, $arrayCollection);
//		$collection->setInitialized(true);
//		$collection->takeSnapshot();
//
//		return $collection;
//	}
//	private function loadToOneEntity($entity, $targetClassName, $targetColumn, $targetMetadata, $targetValue = null)
//	{
//		$em = ObjectRepository::getEntityManager(PageController::SCHEMA_AUDIT);
//
//		$revision = $entity->getRevisionId();
//		$entityId = $entity->getId();
//
//
//		if ( ! is_null($targetValue)) {
//			$entityId = $targetValue;
//		}
//
//		/*
//		  $qb = $em->createQueryBuilder();
//		  $qb->select('e')
//		  ->from($targetClassName, 'e')
//		  ->where('e.'.$targetColumn.' = ?0')
//		  ->andWhere('e.revision <= ?1')
//		  ->orderBy('e.revision', 'DESC')
//		  ->setMaxResults(1)
//		  ->setParameters(array($entityId, $revision))
//		  ;
//
//		  $query = $qb->getQuery();
//		  $entity = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_OBJECT);
//		 */
//
//		//$map = $targetMetadata->auditAssociationMappings[$targetColumn];
//		//$tgCol = $map['fieldName'];
//
//
//		$qb = $em->createQueryBuilder();
//		$qb->select('max(e.revision) as revision, e.id')
//				->from($targetClassName, 'e')
//				->where('e.id = ?0')
//				->andWhere('e.revision <= ?1')
//				->groupBy('e.revision')
//				->setParameters(array($entityId, $revision))
//		;
//
//		$query = $qb->getQuery();
//		$primaryKeys = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
//
//
//		$keys = array_shift($primaryKeys);
//
//		if (empty($keys)) {
//			return null;
//		}
//
//
//		$entity = $em->getReference($targetClassName, $keys);
//
//		return $entity;
//	}

	/**
	 * @param array $data
	 */
	public function setRevision($data)
	{
		$this->revision = $data['revision'];
	}

}