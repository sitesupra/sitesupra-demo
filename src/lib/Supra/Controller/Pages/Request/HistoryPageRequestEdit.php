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
use Supra\Controller\Pages\Entity\PageLocalizationPath;
use Supra\Controller\Pages\Entity\PageLocalization;
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
	 * Does the history localization version restoration
	 */
	public function restoreLocalization()
	{
		$draftEntityManager = ObjectRepository::getEntityManager(PageController::SCHEMA_DRAFT);
		$auditEm = ObjectRepository::getEntityManager(PageController::SCHEMA_AUDIT);
				
		$draftEntityManager->getEventManager()
				->dispatchEvent(AuditEvents::localizationPreRestoreEvent);
		
		$auditLocalization = $this->getPageLocalization();
		
		$localization = $draftEntityManager->merge($auditLocalization);
		
		// Need to load the path entity if it exists so further creation knows to insert or update it.
		if ($localization instanceof PageLocalization) {
			$pathEntity = $draftEntityManager->find(PageLocalizationPath::CN(), $localization->getId());
			
			if ( ! is_null($pathEntity)) {
				$localization->setPathEntity($pathEntity);
			}
		}
	
		// merge placeholders
		// FIXME: I think also parent template placeholders are merged here. Isn't that a problem?
		$auditPlaceHolders = $auditLocalization->getPlaceHolders();
		foreach ($auditPlaceHolders as $placeHolder) {
			
			if ( ! $this->isLocalResource($placeHolder)) {
				continue;
			}
			
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
			
			if ( ! $this->isLocalResource($auditBlock)) {
				continue;
			}
			
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
			
			if ( ! $this->isLocalResource($auditProperty)) {
				continue;
			}
			
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
//		$auditEm = ObjectRepository::getEntityManager(PageController::SCHEMA_AUDIT);

		$page = $this->getPageLocalization()
				->getMaster();
			
		$pageId = $page->getId();

		$draftPage = $draftEm->merge($page);
		/* @var $draftPage AbstractPage */
		
		
		// Find the repository
		$entityName = $draftPage->getNestedSetRepositoryClassName();
		$repository = $draftEm->getRepository($entityName);
		
		
		// In case of refresh it already exists..
		$doctrineNode = $draftPage->getNestedSetNode();
			
		// .. create new if doesn't
		if (is_null($doctrineNode)) {
			// Initialize the doctrine nested set node
			$doctrineNode = new \Supra\NestedSet\Node\DoctrineNode($repository);
			$draftPage->setNestedSetNode($doctrineNode);
		}
		$doctrineNode->belongsTo($draftPage);
		
		$draftEm->getRepository(AbstractPage::CN())
				->getNestedSetRepository()
				->add($draftPage);

		$pageLocalizations = $page->getLocalizations();

		foreach ($pageLocalizations as $localization) {
			
//			$auditEm->detach($localization);
//			
//			if ($localization instanceof Entity\PageLocalization) {
//				$localization->resetPath();
//				$localization->initializeProxyAssociations();
//			}

			$draftLocalization = $draftEm->merge($localization);
			$draftPage->setLocalization($draftLocalization);
			
			if ($localization instanceof Entity\PageLocalization) {
				$draftLocalization->resetPath();
			}
			
			$this->setPageLocalization($localization);

			$placeHolders = $localization->getPlaceHolders();
			foreach ($placeHolders as $placeHolder) {
				/* @var $placeHolder Entity\Abstraction\PlaceHolder */
				$draftEm->merge($placeHolder);
				
				$blocks = $placeHolder->getBlocks();
				
				foreach ($blocks as $block) {
					$draftEm->merge($block);
				}
			}
			
			// block properties from audit
			$properties = $this->getBlockPropertySet();
			
			foreach ($properties as $property) {
				/* @var $property Entity\BlockProperty */
				if ( ! $this->isLocalResource($property)) {
					continue;
				}
				
//				$this->loadPropertyMetadata($property);
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
	
	/**
	 * Overriden method, will return one item page set if the passed template is currently being restored.
	 * @param Entity\Template $template
	 * @return Set\PageSet
	 */
	protected function getTemplateTemplateHierarchy(Entity\Template $template)
	{
		if ($this->isLocalResource($template)) {
			// Maybe it's enough to assume it is root template in the beginning?
			return new Set\PageSet(array($template));
		} else {
			return parent::getTemplateTemplateHierarchy($template);
		}
	}
	
	/**
	 * @return \Supra\Controller\Pages\Set\BlockSet
	 */
	public function getBlockSet()
	{
		if (isset($this->blockSet)) {
			return $this->blockSet;
		}
		
		$blockSet = parent::getBlockSet();

		$uniqueBlockArray = array();
		
		foreach($blockSet as $block) {
			
			$blockId = $block->getId();
			if ( ! isset($uniqueBlockArray[$blockId])) {
				$uniqueBlockArray[$blockId] = $block;
				continue;
			}
			
			if ($uniqueBlockArray[$blockId]->getRevisionId() < $block->getRevisionId()) {
				$uniqueBlockArray[$blockId] = $block;
			}
		}
		
		$this->blockSet->exchangeArray($uniqueBlockArray);

		return $this->blockSet;
	}
	
}
