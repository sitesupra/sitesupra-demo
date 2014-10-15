<?php

namespace Supra\Package\Cms\Pages\Request;

use Supra\Database\Doctrine\Hydrator\ColumnHydrator;
use Doctrine\ORM\Query;
use Supra\Controller\Pages\Entity;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Exception;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Entity\Abstraction\AbstractPage;
use Supra\Controller\Pages\Event\PageEventArgs;
use Supra\Controller\Pages\Event\AuditEvents;
use Doctrine\Common\Collections\Collection;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Controller\Pages\Entity\PageLocalizationPath;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Supra\Controller\Pages\Listener\PagePathGenerator;

/**
 * Request object for edit mode requests
 */
class PageRequestEdit extends PageRequest
{
	/**
	 * {@inheritdoc}
	 * @var boolean
	 */
	protected $allowFlushing = true;
	
	/**
	 * Used as temporary container for cloned entities
	 * see recursiveClone() method
	 * @var array
	 */
	private $_clonedEntities = array();

	/**
	 * @var array
	 */
	private $_clonedEntitySources = array();

	/**
	 * recursiveClone() method recursion depth
	 * @var int
	 */
	private $_cloneRecursionDepth;
	
	/**
	 * Factory method for page request edit mode
	 * @param Entity\Abstraction\Localization $localization
	 * @param string $media
	 * @return PageRequestEdit
	 */
	public static function factory(Entity\Abstraction\Localization $localization, $media = Entity\TemplateLayout::MEDIA_SCREEN)
	{
		throw new \Exception('Fixme');

		$locale = $localization->getLocale();
		$instance = null;
		
		if ($localization instanceof Entity\GroupLocalization) {
			$instance = new GroupPageRequest($locale, $media);
		} else {
			$instance = new PageRequestEdit($locale, $media);
		}
		
		$instance->setPageLocalization($localization);
		
		return $instance;
	}

	/**
	 *
	 */
	public function publish()
	{
		$draftEm = $this->getDoctrineEntityManager();
		$publicEm = ObjectRepository::getEntityManager(PageController::SCHEMA_PUBLIC);
		
		if ($draftEm == $publicEm) {
			$this->log->debug("Publish doesn't do anything because CMS and public database connections are identical");
			
			return;
		}
		
		$this->createMissingPlaceHolders();
		
		$draftData = $this->getLocalization();
		
		if ($draftData instanceof Entity\PageLocalization) {
			// Set creation time if empty
			if ( ! $draftData->isPublishTimeSet()) {
				$draftData->setCreationTime();
				
				$draftEm->flush();
			}
			
			// Pages also need to be checked for path duplicates
			$locale = $draftData->getLocale();
			$path = $draftData->getPathEntity()->getPath();
			
			// Check only if path is not null
			if ( ! is_null($path)) {
				$pathString = $path->getFullPath();
				$pathRepository = $publicEm->getRepository(PageLocalizationPath::CN());

				$criteria = array(
					'locale' => $locale,
					'path' => $path,
				);

				$duplicate = $pathRepository->findOneBy($criteria);
				if ( ! is_null($duplicate) && ! $draftData->getPathEntity()->equals($duplicate)) {
					throw new Exception\RuntimeException("Another page with path $pathString already exists");
				}
			}
		}
		
		$pageId = $draftData->getMaster()->getId();
		$localeId = $draftData->getLocale();

		$draftPage = $draftData->getMaster();
		
		/*
		 * NB!
		 * This is important to load the public page first before merging the 
		 * data into the public scheme because doctrine will create abstract not
		 * usable proxy class for it otherwise
		 */
		/* @var $publicPage Entity\Abstraction\AbstractPage */
		$publicPage = $publicEm->find(AbstractPage::CN(), $pageId);
		$oldRedirect = $newRedirect = null;
				
		// AbstractPage is not inside the public scheme yet
		if (empty($publicPage)) {
			throw new Exception\LogicException("Page {$pageId} is not found inside the public scheme");
		} else {
			// Remove the old redirect link referenced element
			$publicData = $publicPage->getLocalization($localeId);

			if ($publicData instanceof Entity\PageLocalization) {
				$oldRedirect = $publicData->getRedirect();
			}
		}
		
//		$publicEm->getProxyFactory()->getProxy(Entity\ReferencedElement\LinkReferencedElement::CN(), -1);
//		$publicEm->getProxyFactory()->getProxy(PageLocalizationPath::CN(), -1);

		// This IS still actual, after ORM version up to 2.2.0
		// @TODO check in 2.3? 
		// Initialize, because not initialized proxy objects are not merged
		if ($draftData instanceof PageLocalization) {
			$draftData->initializeProxyAssociations();
		}
		
		// Merge the data element
		$publicData = $publicEm->merge($draftData);
		$publicData->setMaster($publicPage);
		
		/* @var $publicData Entity\Localization */
		$publicData->setLock(null);
		
		if ($publicData instanceof Entity\PageLocalization) {
			$newRedirect = $publicData->getRedirect();
		}
		
		// Tags
		$tagCollection = $draftData->getTagCollection();
		$oldTagCollection = $publicData->getTagCollection();
		
		foreach ($tagCollection as $tag) {
			$publicEm->merge($tag);
		}
		
		foreach ($oldTagCollection as $tag) {
			$name = $tag->getName();
			if ( ! $tagCollection->offsetExists($name)) {
				$publicEm->remove($tag);
			}
		}
		
		// 0. Copy placeHolder groups
		$placeHolderGroups = $draftData->getPlaceHolderGroups();
		foreach ($placeHolderGroups as $group) {
			$publicEm->merge($group);
			
			$placeHolders = $group->getPlaceholders();
			foreach ($placeHolders as $placeHolder) {
				$publicEm->merge($placeHolder);
			}
		}
		
		// 1. Get all blocks to be copied
		$draftBlocks = $this->getBlocksInPage($draftEm, $draftData);

		// 2. Get all blocks existing in public
		$existentBlocks = $this->getBlocksInPage($publicEm, $publicData);

		// 3. Remove blocks in 2, not in 1, Plaremove all referencing block properties first
		$draftBlockIdList = Entity\Abstraction\Entity::collectIds($draftBlocks);
		$existentBlockIdList = Entity\Abstraction\Entity::collectIds($existentBlocks);
		$removedBlockIdList = array_diff($existentBlockIdList, $draftBlockIdList);
		
		if ( ! empty($removedBlockIdList)) {
			//$this->removeBlocks($publicEm, $removedBlockIdList);
			foreach($removedBlockIdList as $removedBlockId) {
				foreach($existentBlocks as $block) {
					if ($block->getId() == $removedBlockId) {
						$publicEm->remove($block);
						break;
					}
				}
			}
		}
		
		$placeHolderIds = array();
		$placeHolderNames = array();
		
		// 4. Merge all placeholders, don't delete not used, let's keep them
		foreach ($draftBlocks as $block) {
			$placeHolder = $block->getPlaceHolder();
			$publicEm->merge($placeHolder);
			
			$placeHolderId = $placeHolder->getId();
			$placeHolderName = $placeHolder->getName();
			$placeHolderIds[$placeHolderId] = $placeHolderId;
			$placeHolderNames[$placeHolderName] = $placeHolderName;
		}
		
		/*
		 * For some reason in some cases Doctrine couldn't insert block because 
		 * placeholder wasn't yet created
		 */
		$publicEm->flush();
		
		// 4.2. Delete not used placeholders
		$placeHolderEntity = Entity\Abstraction\PlaceHolder::CN();
		$localizationId = $draftData->getId();
		$placeHolderIds = array_values($placeHolderIds);
		$placeHolderNames = array_values($placeHolderNames);
		
		if ( ! empty($placeHolderIds)) {
		
			$notUsedPlaceHolders = $publicEm->createQuery("SELECT ph FROM $placeHolderEntity ph
					WHERE ph.localization = ?0 AND ph.id NOT IN (?1) AND ph.name IN (?2)")
					->setParameters(array($localizationId, $placeHolderIds, $placeHolderNames))
					->getResult();

			foreach ($notUsedPlaceHolders as $notUsedPlaceHolder) {
				$publicEm->remove($notUsedPlaceHolder);
			}
		}
		
		// 4.3. Flush just to be sure to track erorrs early
		$publicEm->flush();
		

		// 5. Merge all blocks in 1
		foreach ($draftBlocks as $block) {
			$publicEm->merge($block);
		}

		// 6. Get properties to be copied (of a. self and b. template)
		$draftProperties = $this->getBlockPropertySet()
				//->getPageProperties($draftData);
				;
				
		$draftPropertyIds = $draftProperties->collectIds();
		
		$existentProperties = $this->getPageBlockProperties($publicEm, $publicData);
		$existentPropertyIds = Entity\Abstraction\Entity::collectIds($existentProperties);
		
		// 6.1 Remove all page block properties from public schema not existant in draft
		$removedPropertyIdList = array_diff($existentPropertyIds, $draftPropertyIds);
		
		if ( ! empty($removedPropertyIdList)) {
			foreach ($existentProperties as $existentProperty) {
				/* @var $existentProperty Entity\BlockProperty */
				$id = $existentProperty->getId();
				
				if (in_array($id, $removedPropertyIdList, true)) {
					$publicEm->remove($existentProperty);
				}
			}
		}
		
		// 7. For properties 5b get block, placeholder IDs, check their existance in public, get not existant
		$missingBlockIdList = array();

		// Searching for missing parent template blocks IDs in the public schema
		$blockIdList = $draftProperties->getBlockIdList();
		$parentTemplateBlockIdList = array_diff($blockIdList, $draftBlockIdList);
		
		if ( ! empty($parentTemplateBlockIdList)) {
			$blockEntity = Entity\Abstraction\Block::CN();

			$qb = $publicEm->createQueryBuilder();
			$qb->from($blockEntity, 'b')
					->select('b.id')
					->where($qb->expr()->in('b', $parentTemplateBlockIdList));

			$query = $qb->getQuery();
			$existentBlockIdList = $query->getResult(ColumnHydrator::HYDRATOR_ID);

			$missingBlockIdList = array_diff($parentTemplateBlockIdList, $existentBlockIdList);
		}

		// 8. Merge missing place holders from 7 (reset $locked property)
		$draftPlaceHolderIdList = $this->getPlaceHolderIdList($draftEm, $missingBlockIdList);
		$publicPlaceHolderIdList = $this->loadEntitiesByIdList($publicEm, 
				Entity\Abstraction\PlaceHolder::CN(),
				$draftPlaceHolderIdList,
				'e.id',
				ColumnHydrator::HYDRATOR_ID);
		$missingPlaceHolderIdList = array_diff($draftPlaceHolderIdList, $publicPlaceHolderIdList);

		$missingPlaceHolders = $this->loadEntitiesByIdList($draftEm,
				Entity\Abstraction\PlaceHolder::CN(),
				$missingPlaceHolderIdList);

		/* @var $placeHolder Entity\Abstraction\PlaceHolder */
		foreach ($missingPlaceHolders as $placeHolder) {
			$placeHolder = $publicEm->merge($placeHolder);
			
			// Reset locked property
			if ($placeHolder instanceof Entity\TemplatePlaceHolder) {
				$placeHolder->setLocked(false);
			}
		}

		// 9. Merge missing blocks (add $temporary property)
		$missingBlocks = $this->loadEntitiesByIdList($draftEm, Entity\TemplateBlock::CN(), $missingBlockIdList);

		/* @var $block Entity\TemplateBlock */
		foreach ($missingBlocks as $block) {
			$block = $publicEm->merge($block);
			$block->setTemporary(true);
		}

		// 10. Clear all property metadata on the public going to be merged
		//TODO: remove reference elements as well!
		$propertyIdList = $draftProperties->collectIds();
		
		// 12. Remove old redirect if exists and doesn't match with new one
		// NOTE: remove() on publicEm should be called before publicEm::unitOfWork will be clear()'ed (code below)
		// or removing will fail ("detached entity could not be removed")
		if ( ! is_null($oldRedirect)) {
			if (is_null($newRedirect) || ! $oldRedirect->equals($newRedirect)) {
				$publicEm->remove($oldRedirect);
			}
		}
		
		if ( ! empty($propertyIdList)) {
			
			$qb = $publicEm->createQueryBuilder();
			$metaData = $qb->select('m')
					->from(Entity\BlockPropertyMetadata::CN(), 'm')
					->where($qb->expr()->in('m.blockProperty', $propertyIdList))
					->getQuery()->execute();
			$metaDataIds = Entity\Abstraction\Entity::collectIds($metaData);
			
			if ( ! empty($metaDataIds)) {
				
				$qb = $publicEm->createQueryBuilder();
				$subProperties = $qb->select('bp')
						->from(Entity\BlockProperty::CN(), 'bp')
						->where($qb->expr()->in('bp.masterMetadataId', $metaDataIds))
						->getQuery()->execute();
				
				foreach ($subProperties as $subProperty) {
					$publicEm->remove($subProperty);
				}
				
//				$qb = $publicEm->createQueryBuilder();
//				$subProperties = $qb->select('p')
//						->from(Entity\BlockProperty::CN(), 'p')
//						->where($qb->expr()->in('p.masterMetadata', $metaDataIds))
//						->getQuery()->execute();
//				$subPropertyIds = Entity\Abstraction\Entity::collectIds($subProperties);
//
//				if ( ! empty($subPropertyIds)) {
//					$qb = $publicEm->createQueryBuilder();
//					$qb->delete(Entity\BlockPropertyMetadata::CN(), 'm')
//							->where($qb->expr()->in('m.blockProperty', $subPropertyIds))
//							->getQuery()->execute();
//
//					$qb = $publicEm->createQueryBuilder();
//					$qb->delete(Entity\BlockProperty::CN(), 'p')
//							->where($qb->expr()->in('p.id', $subPropertyIds))
//							->getQuery()->execute();
//				}
//
			}
			
			$qb = $publicEm->createQueryBuilder();
			$qb->delete(Entity\BlockPropertyMetadata::CN(), 'r')
					->where($qb->expr()->in('r.blockProperty', $propertyIdList))
					->getQuery()->execute();
			
			// Force to clear UoW, or #11 step will fail, 
			// as properties are still marked as existing inside EM
			$publicEm->flush();
			$publicEm->getUnitOfWork()->clear();
		}
		
		// 11. Merge all properties from 5
		$ownedProperties = array();
		foreach ($draftProperties as $property) {
			
			// Initialize the property metadata so it is copied as well
			/* @var $property Entity\BlockProperty */
			$metadata = $property->getMetadata();
			if ($metadata instanceof \Doctrine\ORM\PersistentCollection) {
				$metadata->initialize();
			}
			
			//foreach($metadata as $metadataItem) {
			//	$subProperties = $metadataItem->getMetadataProperties();
			//	if ($subProperties instanceof \Doctrine\ORM\PersistentCollection) {
			//		$subProperties->initialize();
			//	}
			//}
			
			// Skip shared properties
			if ( ! $property instanceof Entity\SharedBlockProperty) {
				// for now also skip sub-properties, they'll be merged later
				$owner = $property->getMasterMetadata();
				if ( ! is_null($owner)) {
					$ownedProperties[] = $property;
					continue;
				}
				
				$publicEm->merge($property);
			}
		}
			
		$publicEm->flush();

		// sub-properties
		foreach($ownedProperties as $property) {
			$publicEm->merge($property);
		}
		
		$draftEm->flush();
		$publicEm->flush();
				
		$pageEventArgs = new PageEventArgs();
		$pageEventArgs->setEntityManager($draftEm);
		// fixtures are using this
		$pageEventArgs->setProperty('referenceId', $publicData->getId());
		
		$draftEm->getEventManager()
				->dispatchEvent(AuditEvents::pagePublishEvent, $pageEventArgs);
	}
	
	/**
	 * Loads blocks from the current page
	 * @param EntityManager $em
	 * @param Entity\Abstraction\Localization $localization
	 * @return array 
	 */
	private function getBlocksInPage(EntityManager $em, Entity\Abstraction\Localization $localization)
	{
		$localizationId = $localization->getId();
		$blockEntity = Entity\Abstraction\Block::CN();
		
		$dql = "SELECT b FROM $blockEntity b 
				JOIN b.placeHolder ph
				WHERE ph.localization = ?0";
		
		$blocks = $em->createQuery($dql)
				->setParameters(array($localizationId))
				->getResult();
		
		foreach ($blocks as $key => $block) {
			if ($block instanceof Entity\PageBlock) {
				if ($block->isInactive()) {
					unset($blocks[$key]);
				}
			}
		}
		
		return $blocks;
	}
	
	/**
	 * @FIXME: merge with createMissingPlaceHolders() method
	 */
	protected function copyBlocksFromTemplate()
	{
		$localization = $this->getLocalization();
		
		if ( ! $localization instanceof Entity\PageLocalization) {
			return;
		}
	
		$locale = $localization->getLocale();
		
		$templateLocalization = $localization->getTemplate()
				->getLocalization($locale);

		$templatePlaceHolders = $templateLocalization->getPlaceHolders();
		$placeHolders = $localization->getPlaceHolders();
		
		$em = $this->getDoctrineEntityManager();
		
		foreach ($placeHolders as $placeHolder) {
			
			$name = $placeHolder->getName();
			
			// 0. searching for the same placeholder in template localization
			if ( ! $templatePlaceHolders->offsetExists($name)) {
				continue;
			}
						
			// 1. copy template blocks
			$templatePlaceHolder = $templatePlaceHolders->offsetGet($name);
			$templateBlocks = $templatePlaceHolder->getBlocks();
			
			// Z. apply template placeholder group settings
			$templatePlaceHolderGroup = $templatePlaceHolder->getGroup();
			if ($templatePlaceHolderGroup !== null) {
				$placeHolderGroup = $placeHolder->getGroup();
				if ($placeHolderGroup !== null) {
					$placeHolderGroup->setGroupLayoutName($templatePlaceHolderGroup->getGroupLayoutName());
				}
			}
			
			$blocksFromTemplate = array();
			
			$classes = array();
			$oldTemplateBlockMap = array();
			
			foreach ($templateBlocks as $block) {
				
				$newBlock = Entity\Abstraction\Block::factoryClone($localization, $block);
				
				$classes[] = $block->getComponentClass();
				$blocksFromTemplate[] = $newBlock;
				
				$oldTemplateBlockMap[$newBlock->getId()] = $block;
			}
			
			// 2. maintain blocks associations
			//$blocks = $placeHolder->getBlocks();
			
			$blockCn = Entity\PageBlock::CN();
			$blocks = $em->createQuery("SELECT b FROM {$blockCn} b WHERE b.placeHolder = :id ORDER by b.position ASC")
					->setParameter('id', $placeHolder->getId())
					->getResult();
			
			
			$usedBlocks = array();
			
			foreach ($blocks as $block) {
				
				$matchedBlocks = array();
				
				$blockClass = $block->getComponentClass();
				
				// 2.1. if there is no such block in template, hide it and skip
				if ( ! in_array($blockClass, $classes)) {
					$block->setInactive(true);
					continue;
				}
				
				// 2.2. find the most suitable block in template
				foreach ($blocksFromTemplate as $templateBlock) {
					
					if (in_array($templateBlock->getId(), $usedBlocks)) {
						continue;
					}
					
					if ($templateBlock->getComponentClass() != $blockClass) {
						continue;
					}
					
					$positionMatch = null;
					$oldPosition = $block->getPosition();
					$positionInTemplate = $templateBlock->getPosition();
					
					if ($oldPosition == $positionInTemplate) {
						$positionMatch = 0;
					}
					else {
						$positionMatch = abs($oldPosition - $positionInTemplate);
					}
					
					$matchedBlocks[$positionMatch] = $templateBlock;
				}
				
				if (empty($matchedBlocks)) {
					$block->setInactive(true);
					continue;
				}
				
				krsort($matchedBlocks);
				$matchedBlock = array_shift($matchedBlocks);
				
				$properties = $block->getBlockProperties();
				
				$metadataMap = array();
				$clonedProperties = array();
				
				foreach ($properties as $property) {
					
					/* @var $property \Supra\Controller\Pages\Entity\BlockProperty */		
					$metadataCollection = $property->getMetadata();
					$blockProperty = clone($property);
					
					$blockProperty->resetLocalization();
					$blockProperty->resetBlock();
						
					$blockProperty->setLocalization($localization);
					$blockProperty->setBlock($matchedBlock);

					$clonedProperties[] = $blockProperty;
					
					foreach ($metadataCollection as $metadata) {
						/* @var $metadata \Supra\Controller\Pages\Entity\BlockPropertyMetadata */

						$newMetadata = clone($metadata);
						$newMetadata->setBlockProperty($blockProperty);
						$em->persist($newMetadata);
							
						$metadataMap[$metadata->getId()] = $newMetadata;
					}
						
					$em->persist($blockProperty);
					$em->remove($property);
				}
				
				foreach ($clonedProperties as $property) {
					if ($property->getMasterMetadataId() !== null) {
						$metaId = $property->getMasterMetadataId();
						if (isset($metadataMap[$metaId])) {
							$property->setMasterMetadata($metadataMap[$metaId]);
						}
					}
				}	
				
				$em->remove($block);
				$usedBlocks[] = $matchedBlock->getId();
			}
			
			foreach ($blocksFromTemplate as $block) {
				if ( ! in_array($block->getId(), $usedBlocks)) {
					
					$sourceBlock = $oldTemplateBlockMap[$block->getId()];
					
					$properties = $sourceBlock->getBlockProperties();
				
					$metadataMap = array();
					$clonedProperties = array();

					foreach ($properties as $property) {

						/* @var $property \Supra\Controller\Pages\Entity\BlockProperty */		
						$metadataCollection = $property->getMetadata();
						$blockProperty = clone($property);

						$blockProperty->resetLocalization();
						$blockProperty->resetBlock();

						$blockProperty->setLocalization($localization);
						$blockProperty->setBlock($block);

						$clonedProperties[] = $blockProperty;

						foreach ($metadataCollection as $metadata) {
							/* @var $metadata \Supra\Controller\Pages\Entity\BlockPropertyMetadata */

							$newMetadata = clone($metadata);
							$newMetadata->setBlockProperty($blockProperty);
							$em->persist($newMetadata);

							$metadataMap[$metadata->getId()] = $newMetadata;
						}

						$em->persist($blockProperty);
					}

					foreach ($clonedProperties as $property) {
						if ($property->getMasterMetadataId() !== null) {
							$metaId = $property->getMasterMetadataId();
							if (isset($metadataMap[$metaId])) {
								$property->setMasterMetadata($metadataMap[$metaId]);
							}
						}
					}
				
					
				}
			}
			
			foreach ($blocksFromTemplate as $block) {
				$em->persist($block);
				$block->setPlaceHolder($placeHolder);
			}
		}
	}
	
//	/**
//	 * Removes blocks with all properties by ID
//	 * @param EntityManager $em
//	 * @param array $blockIdList
//	 */
//	private function removeBlocks(EntityManager $em, array $blockIdList)
//	{
//		if (empty($blockIdList)) {
//			return;
//		}
//		
//		$blockPropetyEntity = Entity\BlockProperty::CN();
//		$blockEntity = Entity\Abstraction\Block::CN();
//		
//		$qb = $em->createQueryBuilder();
//		$qb->delete($blockPropetyEntity, 'p')
//				->where($qb->expr()->in('p.block', $blockIdList))
//				->getQuery()
//				->execute();
//		
//		$qb = $em->createQueryBuilder();
//		$qb->delete($blockEntity, 'b')
//				->where($qb->expr()->in('b', $blockIdList))
//				->getQuery()
//				->execute();
//	}
	
	/**
	 * Load place holder ID list from block ID list
	 * @param EntityManager $em
	 * @param array $blockIdList
	 * @return array
	 */
	private function getPlaceHolderIdList(EntityManager $em, array $blockIdList)
	{
		if (empty($blockIdList)) {
			return array();
		}
		
		$qb = $em->createQueryBuilder();
		$qb->from(Entity\Abstraction\Block::CN(), 'b')
				->join('b.placeHolder', 'p')
				->select('DISTINCT p.id')
				->where($qb->expr()->in('b', $blockIdList));
		$query = $qb->getQuery();
		$placeHolderIdList = $query->getResult(ColumnHydrator::HYDRATOR_ID);
		
		return $placeHolderIdList;
	}
	
	/**
	 * Loads entities by ID list
	 * @param EntityManager $em
	 * @param string $entity
	 * @param array $idList 
	 * @return array
	 */
	private function loadEntitiesByIdList(EntityManager $em, $entity, array $idList, $select = 'e', $hydrationMode = Query::HYDRATE_OBJECT)
	{
		if (empty($idList)) {
			return array();
		}
		
		$qb = $em->createQueryBuilder();
		$qb->from($entity, 'e')
				->select($select)
				->where($qb->expr()->in('e', $idList));
		$query = $qb->getQuery();
		$list = $query->getResult($hydrationMode);
		
		return $list;
	}
	
	/**
	 * Disallows automatic flushing
	 */
	public function blockFlushing()
	{
		$this->allowFlushing = false;
	}
	
	/** 
	 * Deletes all page published localizations from public schema
	 */
	public function unPublish()
	{
		
		$publicEm = ObjectRepository::getEntityManager('#public');
		$publicEm->getConnection()->beginTransaction();
		
		try {
			
			$page = $this->getLocalization()
					->getMaster();
			
			$page = $publicEm->find(AbstractPage::CN(), $page->getId());
			
			$localizationSet = $page->getLocalizations();
			
			foreach($localizationSet as $localization) {
				
				$localization = $publicEm->find(Entity\Abstraction\Localization::CN(), $localization->getId());
	
				$blocks = $this->getBlocksInPage($publicEm, $localization);
				foreach($blocks as $block) {
					$publicEm->remove($block);
				}

				$properties = $this->getPageBlockProperties($publicEm, $localization);
				foreach ($properties as $property) {
					$publicEm->remove($property);
				}

				$publicEm->remove($localization);
			}
			
			// Remove published placeholders
			/*$placeHolders = $page->getPlaceHolders();*/
			$placeHolders = $this->getPlaceHolders($publicEm);
			foreach($placeHolders as $placeHolder) {
				$publicEm->remove($placeHolder);
			}
			
			$placeHolderGroups = $this->getPlaceHolderGroups($publicEm);
			foreach($placeHolderGroups as $placeHolderGroup) {
				$publicEm->remove($placeHolderGroup);
			}
			
			$publicEm->flush();

		} catch (\Exception $e) {
			
			$publicEm->getConnection()->rollBack();
			throw $e;
		}
		
		$publicEm->getConnection()->commit();
	}
	
	/**
	 * Delete page (AbstractPage and all related Localizations)
	 */
	public function delete()
	{
		// 1. remove any published version first
		$this->unPublish();
		
		// 2. fire pageDeleteEvent which will cause EntityAuditListener to
		// catch all entity deletions and store them under single revision
		$draftEm = $this->getDoctrineEntityManager();
		$eventManager = $draftEm->getEventManager();
		
		$localization = $this->getLocalization();
		$masterId = $localization->getMaster()
				->getId();
		
		$pageEventArgs = new PageEventArgs();
		$pageEventArgs->setProperty('master', $localization->getMaster());
		$eventManager->dispatchEvent(AuditEvents::pagePreDeleteEvent, $pageEventArgs);
	
		// 3. remove master from draft.
		// all related entites will be removed by cascade removal
		$removePage = function() use ($draftEm, $masterId) {
			
			$master = $draftEm->find(AbstractPage::CN(), $masterId);
			
			if ( ! is_null($master)) {
				$draftEm->remove($master);
			}
		};
		
		$draftEm->transactional($removePage);
		
		// 4. return EntityAuditListener in normal state
		$eventManager->dispatchEvent(AuditEvents::pagePostDeleteEvent);
	}
	
	/**
	 * 
	 * @param EntityManager $em
	 * @param Entity\Abstraction\Localization $localization
	 * @return array
	 */
	private function getPageBlockProperties($em, $localization)
	{
		$propertyEntity = Entity\BlockProperty::CN();
		
		$dql = "SELECT bp FROM $propertyEntity bp 
				WHERE bp.localization = ?0";
		
		$properties = $em->createQuery($dql)
				->setParameters(array($localization->getId()))
				->getResult();
		
		return $properties;
	}
	
	/**
	 * 
	 * @param EntityManager $em
	 * @return array
	 */
	private function getPlaceHolders($em)
	{
		$placeHolderEntity = Entity\Abstraction\PlaceHolder::CN();
		$localizationId = $this->getLocalization()
				->getId();

		$dql = "SELECT ph FROM $placeHolderEntity ph
				WHERE ph.localization = ?0";
		
		$placeHolders = $em->createQuery($dql)
				->setParameters(array($localizationId))
				->getResult();
		
		return $placeHolders;
	}
	
	/**
	 * 
	 * @param EntityManager $em
	 * @return array
	 */
	private function getPlaceHolderGroups($em)
	{
		$placeHolderGroupEntity = Entity\PlaceHolderGroup::CN();
		$localizationId = $this->getLocalization()
				->getId();

		$dql = "SELECT phg FROM $placeHolderGroupEntity phg
				WHERE phg.localization = ?0";
		
		$placeHolderGroups = $em->createQuery($dql)
				->setParameters(array($localizationId))
				->getResult();
		
		return $placeHolderGroups;
	}

	/**
	 * Recursively clone the page or page localization object
	 * @param Entity\Abstraction\Entity $entity
	 * @return Entity\Abstraction\Entity
	 */
	public function recursiveClone($entity, $newLocale = null)
	{
		$masterId = null;

		// make sure it isn't called for localization without making new locale version
		if ( ! empty($newLocale)) {

			if ( ! $entity instanceof Entity\Abstraction\Localization) {
				throw new \RuntimeException("Locale can be passed to clone only with localization entity");
			}

			$masterId = $entity->getMaster()->getId();
		}

		$this->_clonedEntities = array();
		$this->_cloneRecursionDepth = 0;

		$newEntity = $this->recursiveCloneInternal($entity, null, true);
		$this->createBlockRelations();
		$this->recursiveCloneFillOwningSide($newEntity, $masterId, $newLocale);

		return $newEntity;
	}
	
	/**
	 * Recursively goes through entity collections, clones and persists elements from them
	 * 
	 * @param Entity $entity
	 * @param Entity $associationOwner
	 * @param boolean $skipPathEvent
	 * @return Entity
	 */
	private function recursiveCloneInternal($entity)
	{
		$em = $this->getDoctrineEntityManager();
		
		// reset at first iteration
		$this->_cloneRecursionDepth ++;
		
		$entityData = $em->getUnitOfWork()->getOriginalEntityData($entity);
		$classMetadata = $em->getClassMetadata($entity::CN());
		
		$entityHash = ObjectRepository::getObjectHash($entity);
		$newEntity = null;
		
		if ( ! isset($this->_clonedEntities[$entityHash])) {
			$newEntity = clone $entity;
			$this->_clonedEntities[$entityHash] = $newEntity;

			$newEntityHash = ObjectRepository::getObjectHash($newEntity);
			$this->_clonedEntitySources[$newEntityHash] = $entity;
		} else {

			// No need to process to OneToMany and persist I guess..
			$newEntity = $this->_clonedEntities[$entityHash];
			
			$this->_cloneRecursionDepth --;

			return $newEntity;
		}

		foreach ($classMetadata->associationMappings as $fieldName => $association) {

			// Don't visit this association, might get other blocks in template cloning
			if ($entity instanceof Entity\Abstraction\Block && $fieldName == 'blockProperties') {
				continue;
			}

			if ( ! $association['isOwningSide']) {

				$newValue = null;

				if (isset($entityData[$fieldName])) {
					if ($entityData[$fieldName] instanceof Collection) {
						$newValue = new \Doctrine\Common\Collections\ArrayCollection();
						foreach ($entityData[$fieldName] as $offset => $collectionItem) {
							$newChild = $this->recursiveCloneInternal($collectionItem);
							$newValue->offsetSet($offset, $newChild);
						}
					} else {
						$newValue = $this->recursiveCloneInternal($entityData[$fieldName]);
					}

					$objectReflection = new \ReflectionObject($newEntity);
					$propertyReflection = $objectReflection->getProperty($fieldName);
					$propertyReflection->setAccessible(true);
					$propertyReflection->setValue($newEntity, $newValue);
				}
			}
		}

		$em->persist($newEntity);

		$this->_cloneRecursionDepth --;
		
		return $newEntity;
	}

	/**
	 * Creates block relations
	 */
	private function createBlockRelations()
	{
		$newOldId = array();

		// For all cloned blocks collect new-old block ID pairs
		foreach ($this->_clonedEntities as $newEntity) {
			if ($newEntity instanceof Entity\Abstraction\Block) {
				$newEntityHash = ObjectRepository::getObjectHash($newEntity);
				$sourceEntity = $this->_clonedEntitySources[$newEntityHash];
				/* @var $sourceEntity Entity\Abstraction\Block */

				$oldId = $sourceEntity->getId();
				$newId = $newEntity->getId();

				$newOldId[$newId] = $oldId;
			}
		}

		// Nothing to bind
		if (empty($newOldId)) {
			return;
		}

		// Read relations from database at once
		$em = $this->getDoctrineEntityManager();
		$blockRelationCn = Entity\BlockRelation::CN();
		$relations = $em->createQuery("SELECT r FROM $blockRelationCn r WHERE r.blockId IN (:blockIds)")
				->setParameter('blockIds', array_values($newOldId), \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
				->getResult();

		$groupByOldId = array();

		// Group relations by block ID searched
		foreach ($relations as $relation) {
			/* @var $relation Entity\BlockRelation */
			$groupByOldId[$relation->getBlockId()] = $relation;
		}

		// Finally create missing and new relations
		foreach ($newOldId as $newId => $oldId) {
			$relation = null;

			if ( ! isset($groupByOldId[$oldId])) {
				$relation = new Entity\BlockRelation($oldId);
				$em->persist($relation);
			} else {
				$relation = $groupByOldId[$oldId];
			}

			$newRelation = new Entity\BlockRelation($newId, $relation->getGroupId());
			$em->persist($newRelation);
		}
	}

	/**
	 * Fills the new objects UP
	 * @param Entity\Abstraction\Entity $newEntity
	 * @throws \RuntimeException
	 */
	private function recursiveCloneFillOwningSide($newEntity, $masterId = null, $newLocale = null)
	{
		$em = $this->getDoctrineEntityManager();
		$classMetadata = $em->getClassMetadata($newEntity::CN());
		
		foreach ($classMetadata->associationMappings as $fieldName => $association) {

			// Don't visit this association, will get properties from localization
			if ($newEntity instanceof Entity\Abstraction\Block && $fieldName == 'blockProperties') {
				continue;
			}

			$fieldReflection = $classMetadata->reflFields[$fieldName];
			/* @var $fieldReflection \ReflectionProperty */
			$fieldReflection->setAccessible(true);
			$associationValue = $fieldReflection->getValue($newEntity);

			if ( ! $association['isOwningSide']) {

				if ($associationValue instanceof Collection) {
					foreach ($associationValue as $collectionItem) {
						$this->recursiveCloneFillOwningSide($collectionItem, $masterId, $newLocale);
					}
				} else {
					$this->recursiveCloneFillOwningSide($associationValue, $masterId, $newLocale);
				}
			} else {

				if ( ! is_null($associationValue)) {
					$joinedEntityHash = ObjectRepository::getObjectHash($associationValue);

					if (isset($this->_clonedEntities[$joinedEntityHash])) {
						$newJoinedEntity = $this->_clonedEntities[$joinedEntityHash];
						$fieldReflection->setValue($newEntity, $newJoinedEntity);
					} else {
						
						// Not found. Possibilities are:
						// * The object of lower level was cloned (e.g.
						//		localization was cloned, localization-page
						//		association is being checked). Don't need to do
						//		anything.
						// * Block property pointing to parent template block.
						//		Need to try changing if new localization is
						//		being created.

						if ( ! empty($newLocale) && ! empty($masterId)) {

							if ($newEntity instanceof Entity\BlockProperty && $fieldName == 'block') {

								/* @var	$associationValue Entity\Abstraction\Block */

								$oldBlockId = $associationValue->getId();

								// Find block appropriate to bind the property to
								$relation = $em->getRepository(Entity\BlockRelation::CN())
										->findOneByBlockId($oldBlockId);
								/* @var $relation Entity\BlockRelation */
								
								$matchingBlock = null;

								if ($relation instanceof Entity\BlockRelation) {
									$groupId = $relation->getGroupId();

									$matchingBlock = $em->createQueryBuilder()
											->select('b')

											// All required tables
											->from(Entity\BlockRelation::CN(), 'r')
											->from(Entity\Abstraction\Block::CN(), 'b')
											->join('b.placeHolder', 'ph')
											->join('ph.localization', 'l')

											// condition to bind block and relation
											->andWhere('r.blockId = b.id')

											// group condition
											->andWhere('r.groupId = :groupId')
											->setParameter('groupId', $groupId)

											// locale condition
											->andWhere('l.locale = :locale')
											->setParameter('locale', $newLocale)

	//										// master condition
	//										->andWhere('l.master = :masterId')
	//										->setParameter('masterId', $masterId)

											->from(Entity\Abstraction\Block::CN(), 'b2')
											->andWhere('b2.id = :oldBlockId')
											->setParameter('oldBlockId', $oldBlockId)
											->join('b2.placeHolder', 'ph2')
											->join('ph2.localization', 'l2')

											// Find blocks with common parent master
											->andWhere('l.master = l2.master')

											// finally..
											->getQuery()
											->getOneOrNullResult();
								}
								
								// Don't need such property..
								if (empty($matchingBlock)) {
									$em->remove($newEntity);
								} else {
									$fieldReflection->setValue($newEntity, $matchingBlock);
								}
							}
						}
					}
				}
			}
		}

		// Fix BlockProperty::$masterMetadataId
		if ($newEntity instanceof Entity\BlockProperty) {
			$masterMetadataId = $newEntity->getMasterMetadataId();

			if ( ! empty($masterMetadataId)) {
				$masterMetadata = $em->find(Entity\BlockPropertyMetadata::CN(), $masterMetadataId);

				// Might be in some archive or something, will just remove the property pointing to it
				if (empty($masterMetadata)) {
					$em->remove($newEntity);
				} else {
					$hash = ObjectRepository::getObjectHash($masterMetadata);

					if (isset($this->_clonedEntities[$hash])) {
						$newMasterMetadata = $this->_clonedEntities[$hash];
						$newEntity->setMasterMetadata($newMasterMetadata);
					}
				}
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function getEntityManager()
	{
		return $this->container['doctrine.entity_managers.cms'];
	}
}
