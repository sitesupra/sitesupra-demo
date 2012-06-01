<?php

namespace Supra\Controller\Pages\Request;

use Supra\Database\Doctrine\Hydrator\ColumnHydrator;
use Doctrine\ORM\Query;
use Supra\Controller\Pages\Entity;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Exception;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Set;
use Supra\Controller\Pages\Listener\EntityAuditListener;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Entity\Abstraction\AbstractPage;
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\Controller\Pages\Entity\PageRevisionData;
use Supra\Controller\Pages\Event\AuditEvents;
use Supra\Controller\Pages\Event\PageEventArgs;
use Supra\Uri\Path;
use Supra\Database\Entity as DatabaseEntity;

/**
 * Request object for history mode requests
 */
class HistoryPageRequestEdit extends PageRequest
{
	/**
	 * Contains revision id string
	 * @var string
	 */
	private $revision;
	private $revisionArray = array();
	
	/**
	 * {@inheritdoc}
	 */
	protected function isLocalResource(Entity\Abstraction\Entity $entity)
	{
		$auditEntityManager = ObjectRepository::getEntityManager(PageController::SCHEMA_AUDIT);
		
		if ($auditEntityManager->getUnitOfWork()->isInIdentityMap($entity)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * @param string $revision
	 */
	public function setRevision($revision)
	{
		$this->revision = $revision;
	}
	
	public function setRevisionArray($revisions)
	{		
		$this->revisionArray = $revisions;
	}
	
	/**
	 * Does the history localization version restoration
	 */
	public function restoreLocalization()
	{
		$draftEntityManager = ObjectRepository::getEntityManager(PageController::SCHEMA_DRAFT);
		$auditEm = ObjectRepository::getEntityManager(PageController::SCHEMA_AUDIT);
				
		$draftEntityManager->getEventManager()
				->dispatchEvent(AuditEvents::localizationPreRestoreEvent);
		
		$auditLocalization = $this->getPageLocalization();
		$auditEm->detach($auditLocalization);
		
		$localization = $draftEntityManager->merge($auditLocalization);
	
		// merge placeholders
		// FIXME: I think also parent template placeholders are merged here. Isn't that a problem?
		$auditPlaceHolders = $auditLocalization->getPlaceHolders();
		foreach ($auditPlaceHolders as $placeHolder) {
			$draftEntityManager->merge($placeHolder);
		}
		
		$existingDraftBlocks = $this->getBlocksInPage($draftEntityManager);
		$auditBlocks = $this->getBlockSet();
				
		// find un-used blocks and remove them from draft
		$existingDraftBlockIds = Entity\Abstraction\Entity::collectIds($existingDraftBlocks);
		$auditBlockIds = Entity\Abstraction\Entity::collectIds($auditBlocks);
		$blockToRemove = array_diff($existingDraftBlockIds, $auditBlockIds);
		
		foreach ($blockToRemove as $blockToRemoveId) {
			foreach($existingDraftBlocks as $existingDraftBlock) {
				if ($existingDraftBlock->getId() == $blockToRemoveId) {
					$draftEntityManager->remove($existingDraftBlock);
				}
			}
		}
		
		// merge blocks
		// FIXME: I think also parent template blocks are merged here. Isn't that a problem?
		foreach ($auditBlocks as $auditBlock) {
			$draftEntityManager->merge($auditBlock);
		}
		
		// remove all existing draft metadata and related elements
		$qb = $draftEntityManager->createQueryBuilder();
		$qb->select('m')
				->from(Entity\BlockPropertyMetadata::CN(), 'm')
				->join('m.blockProperty', 'bp')
				->where('bp.localization = :localizationId')
				->setParameter('localizationId', $localization->getId())
				;

		$existingDraftMetadata = $qb->getQuery()
				->getResult();
		
		foreach ($existingDraftMetadata as $existingDraftMetadataItem) {
			$draftEntityManager->remove($existingDraftMetadataItem);
			
			$existingDraftReferencedElement = $existingDraftMetadataItem->getReferencedElement();
			if ( ! is_null($existingDraftReferencedElement)) {
				$draftEntityManager->remove($existingDraftReferencedElement);
			}
		}
		
		$draftEntityManager->flush();
		
		// block properties
		$draftProperties = $this->getPageBlockProperties($draftEntityManager);
		$draftPropertyIds = Entity\Abstraction\Entity::collectIds($draftProperties);
					
		$auditProperties = $this->getBlockPropertySet()
				->getPageProperties($auditLocalization);
		$auditPropertyIds = Entity\Abstraction\Entity::collectIds($auditProperties);
		
		$propertyToRemove = array_diff($draftPropertyIds, $auditPropertyIds);
		foreach ($propertyToRemove as $propertyToRemoveId) {
			foreach ($draftProperties as $draftProperty) {
				if ($draftProperty->getId() === $propertyToRemoveId) {
					$draftEntityManager->remove($draftProperty);
					break;
				}
			}
		}
		
		// force to recalculate localization path, relies on PagePathGenerator::onFlush() event
		if ($localization instanceof Entity\PageLocalization) {
			$draftUow = $draftEntityManager->getUnitOfWork();
			$draftUow->propertyChanged($localization, 'pathPart', null, $localization->getPathPart());
			$draftUow->scheduleForUpdate($localization);
			$draftEntityManager->flush();
		}
		
		foreach ($auditProperties as $auditProperty) {
			$draftEntityManager->merge($auditProperty);
			
			$metaData = $auditProperty->getMetadata();
			foreach ($metaData as $metaDataItem) {
				
				$referencedElement = $metaDataItem->getReferencedElement();
				$draftEntityManager->merge($referencedElement);
			}
		}
			
		/*
		
		$auditEntityManager = ObjectRepository::getEntityManager('#audit');
		
		if ( ! empty($auditProperties)) {
			
			$auditMetaData = $this->getAuditMetadataByProperty($auditProperties);
			if ( ! empty($auditMetaData)) {
				foreach($auditMetaData as $metaDataItem) {
					
					$draftEntityManager->merge($metaDataItem);
					
					$entityData = $auditEntityManager->getUnitOfWork()
							->getOriginalEntityData($metaDataItem);
					
					if (isset($entityData['referencedElement_id'])) {
						$referencedElement = $this->getAuditReferencedElement($entityData['referencedElement_id'], $metaDataItem->getRevisionId());
						
						if ( ! is_null($referencedElement)) {
							$draftEntityManager->merge($referencedElement);
						}
					}
				}
			}
		}
		 */
					
		$draftEntityManager->flush();
	
	}
	
	/**
	 * Does the full trash page version restoration (incl. available localizations)
	 */
	public function restorePage()
	{
		// Temporary entity storage, to prevent spl hash reusage
		// fix in UoW doesn't helps, as loadPropertyMetadata() method uses UoW->clear();
		$splObjectHashMemory = array();
		
 		$draftEm = ObjectRepository::getEntityManager(PageController::SCHEMA_DRAFT);
		$auditEm = ObjectRepository::getEntityManager(PageController::SCHEMA_AUDIT);

		$page = $this->getPageLocalization()
				->getMaster();
			
		$pageId = $page->getId();

		$page = $auditEm->getRepository(AbstractPage::CN())
				->findOneBy(array('id' => $pageId, 'revision' => $this->revision));

		$draftPage = $draftEm->merge($page);

		$draftEm->getRepository(AbstractPage::CN())
				->getNestedSetRepository()
				->add($draftPage);

		$pageLocalizations = $auditEm->getRepository(Localization::CN())
				->findBy(array('master' => $pageId, 'revision' => $this->revision));

		foreach($pageLocalizations as $localization) {
			
			$auditEm->detach($localization);
			
			if ($localization instanceof Entity\PageLocalization) {
				$localization->resetPath();
				$localization->initializeProxyAssociations();
			}

			$draftLocalization = $draftEm->merge($localization);
						
			if ($localization instanceof Entity\PageLocalization) {
				$draftLocalization->resetPath();
			}
			
			$this->setPageLocalization($localization);

			$placeHolders = $localization->getPlaceHolders();
			foreach($placeHolders as $placeHolder) {
				$draftEm->merge($placeHolder);
			}
			
			$localizationId = $localization->getId();

			// page blocks from audit
			$blockEntity = Entity\Abstraction\Block::CN();
			$dql = "SELECT b FROM $blockEntity b 
					JOIN b.placeHolder ph
					WHERE ph.localization = ?0 and b.revision = ?1";
			$blocks = $auditEm->createQuery($dql)
					->setParameters(array($localizationId, $this->revision))
					->getResult();
			
			foreach ($blocks as $block) {
				$draftEm->merge($block);
			}

			// block properties from audit
			$propertyEntity = Entity\BlockProperty::CN();
			$dql = "SELECT bp FROM $propertyEntity bp 
					WHERE bp.localization = ?0 and bp.revision = ?1";
			$properties = $auditEm->createQuery($dql)
				->setParameters(array($localizationId, $this->revision))
				->getResult();
			
			$splObjectHashMemory[] = $properties;
			
			foreach ($properties as $property) {
				$this->loadPropertyMetadata($property);
				$draftEm->merge($property);
			}
		}

//		if ($page instanceof Entity\Template && $page->isRoot()) {
//
//			$templateLayouts = $page->getTemplateLayouts();
//			foreach ($templateLayouts as $templateLayout) {
//				//$draftEm->merge($templateLayout);
//				//$trashEm->remove($templateLayout);
//			}
//		}
		
		return $draftPage;
	}
	
	private function getBlocksInPage(EntityManager $em)
	{
		$localizationId = $this->getPageLocalization()->getId();
		$blockEntity = Entity\Abstraction\Block::CN();
		
		$dql = "SELECT b FROM $blockEntity b 
				JOIN b.placeHolder ph
				WHERE ph.localization = ?0";
		
		$blocks = $em->createQuery($dql)
				->setParameters(array($localizationId))
				->getResult();
		
		return $blocks;
	}
	
	private function getPageBlockProperties(EntityManager $em)
	{
		
		$localizationId = $this->getPageLocalization()->getId();
		
		$qb = $em->createQueryBuilder();
		$qb->select('bp')
				->from(Entity\BlockProperty::CN(), 'bp')
				->where('bp.localization = ?0')
				->setParameters(array($localizationId));
		
		$result = $qb->getQuery()
				->getResult();
		
		return $result;
	}
	
	private function loadPropertyMetadata(Entity\BlockProperty $property) 
	{
		$em = $this->getDoctrineEntityManager();
		
		$metadataEntity = Entity\BlockPropertyMetadata::CN();
		
		$name = $property->getName();
		
		$revisionIds = DatabaseEntity::collectIds($this->revisionArray);
		array_push($revisionIds, $this->revision);
				
		$qb = $em->createQueryBuilder();
		$qb->from($metadataEntity, 'm')
				->select('m')
				->where('m.blockProperty = :property AND m.revision IN (:revisions)')
				->orderBy('m.revision', 'DESC')
				;
	
		$query = $qb->getQuery()
				->setHint(Query::HINT_INCLUDE_META_COLUMNS, true);
		
		$elementRevisions = $query->execute(array(
				'property' => $property->getId(),
				'revisions' => $revisionIds,
			), \Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
		
		$metaCollection = new \Doctrine\Common\Collections\ArrayCollection();
		
		if ( ! empty($elementRevisions)) {

			$qb = $em->createQueryBuilder();
			$expr = $qb->expr(); $or = $expr->orX(); $i = 0;

			$usedRevisions = array();

			foreach($elementRevisions as $elementRevision) {
				if ( ! in_array($elementRevision['referencedElement_id'], $usedRevisions)) {
					$and = $expr->andX();
					$and->add($expr->eq('re.id', '?' . (++$i)));
					$qb->setParameter($i, $elementRevision['referencedElement_id']);
					$and->add($expr->gte('re.revision', '?' . (++$i)));
					$qb->setParameter($i, $elementRevision['revision']);
					$or->add($and);

					//have found latest revision for this property, skip all others
					array_push($usedRevisions, $elementRevision['referencedElement_id']);
				}
			}

			$em->getUnitOfWork()->clear();
			$qb->select('re')
					->from(Entity\ReferencedElement\ReferencedElementAbstract::CN(), 're')
					->where($or)
					->orderBy('re.revision', 'DESC')
					;

			$referencedElements = $qb->getQuery()
					->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_OBJECT);
			
			if ( ! empty($referencedElements)) {
				
				$elementIds = array();
				foreach ($referencedElements as $key => $element) {
					if (in_array($element->getId(), $elementIds) || ! in_array($element->getRevisionId(), $revisionIds)) {
						unset($referencedElements[$key]);
						continue;
					}
					
					array_push($elementIds, $element->getId());					
				}
			}
			
			foreach($referencedElements as $key => $element) {
				
				$metadataName = null;
				foreach($elementRevisions as $elementInfo) {
					if ($elementInfo['referencedElement_id'] == $element->getId()) {
						$metadataName = $elementInfo['name'];
					}
				}
				
				if ( ! is_null($metadataName)) {
					$meta = new Entity\BlockPropertyMetadata($metadataName, $property, $element);
					$metaCollection->set($metadataName, $meta);
				}
			}
		}
		
		$property->overrideMetadataCollection($metaCollection);
	}
	
}