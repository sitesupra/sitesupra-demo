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

	public function getSubscribedEvents()
	{
		return array(
			Events::onFlush,
			Events::postLoad,
			Events::loadClassMetadata,
			'setRevision',
		);
	}

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
			}
		}
		
		else if ($entity instanceof BlockProperty) {
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
		$className = $classMetadata->name;
		$class = new ReflectionClass($className);
		
		$associationMappings = $classMetadata->associationMappings;
		$classMetadata->auditAssociationMappings = $associationMappings;
		
		foreach ($associationMappings as $field => $mapping) {

			$targetClassName = $mapping['targetEntity'];

			$targetReflection = new ReflectionClass($targetClassName);

			if ($targetReflection->implementsInterface(AuditedEntityInterface::CN)) {
				
				unset($classMetadata->associationMappings[$field]);

				if ($mapping['type'] !== ClassMetadata::ONE_TO_MANY) {
				
					$column = null;
					if (isset($mapping['joinColumns'][0]['name'])) {
						$column = $mapping['joinColumns'][0]['name'];
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
						'columnName' => ( ! is_null($column) ? $column : null),
					);

					$classMetadata->mapField($fieldMap);
					
				}
			}
		}
		
	}
	
	public function postLoad(LifecycleEventArgs $eventArgs)
	{
		
		if (empty($this->revision)) {
			return;
		}
		
		$entity = $eventArgs->getEntity();
		$entityManager = $eventArgs->getEntityManager();
		
		$className = $entity::CN();
		$classMetadata = $entityManager->getClassMetadata($className);
		

		$auditMappings = $classMetadata->auditAssociationMappings;
		
		if ( ! $classMetadata->isInheritanceTypeNone()) {
			$rootEntity = $classMetadata->rootEntityName;
			$rootMetaData = $entityManager->getClassMetadata($rootEntity);
			
			$auditMappings = array_merge($auditMappings, $rootMetaData->auditAssociationMappings);
		}
				
		
		
		foreach($auditMappings as $field => $mapping) {
	
			$targetEntityName = $mapping['targetEntity'];
			$targetEntityClass = new ReflectionClass($targetEntityName);
			
			$targetMetadata = $entityManager->getClassMetadata($targetEntityName);
				
			if ($targetEntityClass->implementsInterface(AuditedEntityInterface::CN)) {
				
				$value = null;
				
				switch($mapping['type']) {
					case ClassMetadata::ONE_TO_MANY:
						
						$value = $this->loadToManyCollection($entity, $targetEntityName, $mapping['mappedBy'], $targetMetadata);
						
						if ($value === null) {
							 $value = new ArrayCollection;
						}
						
						if ($value instanceof PersistentCollection) {
							$value->setOwner($entity, $mapping);
						}
						
						break;
						
					case ClassMetadata::MANY_TO_ONE:
						
						
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
						1+1;
						throw new \Exception('Unknown mapping type', $mapping['type']);
				}
				
				$reflField = new \ReflectionProperty($className, $field);
				$reflField->setAccessible( true );
				$reflField->setValue($entity, $value);
			}
			
		}
		
		
		//$this->classicPostLoad($eventArgs);
		
	}
	
	private function loadToManyCollection($entity, $targetClassName, $targetColumn, $targetMetadata) 
	{
		$em = ObjectRepository::getEntityManager(PageController::SCHEMA_AUDIT);
		$revision = $entity->getRevisionId();
		
		$entityId = $entity->getId();
		
		$map = $targetMetadata->auditAssociationMappings[$targetColumn];
		$tgCol = $map['fieldName'];
		
		$qb = $em->createQueryBuilder();
		$qb->select('max(e.revision) as revision, e.id')
				->from($targetClassName, 'e')
				->where('e.' . $tgCol . ' = ?0')
				->andWhere('e.revision <= ?1')
				->groupBy('e.revision')
				->setParameters(array($entityId, $revision))
				;
		
		$query = $qb->getQuery();
		$primaryKeys = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
		
		$entities = null;
		if ( ! empty($primaryKeys)) {
			/*
			$qb = $em->createQueryBuilder();
			$expr = $qb->expr(); $or = $expr->orX(); $i = 0;

			foreach($primaryKeys as $keys) {
				
				list($revision, $id) = array_values($keys);
				
				$and = $expr->andX();
				$and->add($expr->eq('e.id', '?' . (++$i)));
				$qb->setParameter($i, $id);
				$and->add($expr->gte('e.revision', '?' . (++$i)));
				$qb->setParameter($i, $revision);
				$or->add($and);
			}

			$qb->select('e')
				->from($targetClassName, 'e')
				->where($or)
				;

			$entities = $qb->getQuery()
					->getResult();
			*/
			
			foreach($primaryKeys as $keys) {
				$entities[] = $em->getReference($targetClassName, $keys);
			}
			
			
		}
		
		$arrayCollection = ( ! empty($entities) ? new ArrayCollection($entities) : new ArrayCollection);
		
		$collection = new PersistentCollection($em, $targetMetadata, $arrayCollection);
		$collection->setInitialized(true);
		$collection->takeSnapshot();
		
		return $collection;
	}
	
	
	private function loadToOneEntity($entity, $targetClassName, $targetColumn, $targetMetadata, $targetValue = null)
	{
		$em = ObjectRepository::getEntityManager(PageController::SCHEMA_AUDIT);
		
		$revision = $entity->getRevisionId();
		$entityId = $entity->getId();
		
		
		if ( ! is_null($targetValue)) {
			$entityId = $targetValue;
		}
		
		/*
		$qb = $em->createQueryBuilder();
		$qb->select('e')
				->from($targetClassName, 'e')
				->where('e.'.$targetColumn.' = ?0')
				->andWhere('e.revision <= ?1')
				->orderBy('e.revision', 'DESC')
				->setMaxResults(1)
				->setParameters(array($entityId, $revision))
				;
		
		$query = $qb->getQuery();
		$entity = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_OBJECT);
		 */
		
		//$map = $targetMetadata->auditAssociationMappings[$targetColumn];
		//$tgCol = $map['fieldName'];
		
		
		$qb = $em->createQueryBuilder();
		$qb->select('max(e.revision) as revision, e.id')
				->from($targetClassName, 'e')
				->where('e.id = ?0')
				->andWhere('e.revision <= ?1')
				->groupBy('e.revision')
				->setParameters(array($entityId, $revision))
				;
		
		$query = $qb->getQuery();
		$primaryKeys = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
		
		
		$keys = array_shift($primaryKeys);
		
		if (empty($keys)) {
			return null;
		}

		
		$entity = $em->getReference($targetClassName, $keys);
		
		return $entity;
		
		
	}
	
	public function setRevision($data) {
		$this->revision = $data['revision'];
	}

}