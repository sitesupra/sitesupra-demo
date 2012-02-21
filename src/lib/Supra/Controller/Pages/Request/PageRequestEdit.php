<?php

namespace Supra\Controller\Pages\Request;

use Supra\Database\Doctrine\Hydrator\ColumnHydrator;
use Doctrine\ORM\Query;
use Supra\Controller\Pages\Entity;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Exception;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Listener\HistoryRevisionListener;
use Doctrine\ORM\Events;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Listener\EntityAuditListener;
use Supra\Controller\Pages\Entity\Abstraction\AbstractPage;
use Supra\User\Entity\User;
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\Controller\Pages\Event\PageEventArgs;
use Supra\Controller\Pages\Event\PageDeleteEventArgs;
use Supra\Controller\Pages\Event\AuditEvents;
use Doctrine\Common\Collections\Collection;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Controller\Pages\Entity\PageLocalizationPath;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Supra\Controller\Pages\Listener\PagePathGenerator;
use Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;

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
	public static function factory(Entity\Abstraction\Localization $localization, $media = Entity\Layout::MEDIA_SCREEN)
	{
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
		
		$draftData = $this->getPageLocalization();
		
		if ($draftData instanceof Entity\PageLocalization) {
			// Set creation time if empty
			if ( ! $draftData->isPublishTimeSet()) {
				$draftData->setCreationTime();
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
		
		$publicEm->getProxyFactory()->getProxy(Entity\ReferencedElement\LinkReferencedElement::CN(), -1);
		$publicEm->getProxyFactory()->getProxy(PageLocalizationPath::CN(), -1);

// TODO: check, is this still actual, after ORM version up to 2.2.0
//		// Initialize, because not initialized proxy objects are not merged
//		if ($draftData instanceof PageLocalization) {
//			$draftData->initializeProxyAssociations();
//		}

		// Merge the data element
		$publicData = $publicEm->merge($draftData);
		$publicData->setMaster($publicPage);
		
		/* @var $publicData Entity\Localization */
		$publicData->setLock(null);
		
		if ($publicData instanceof Entity\PageLocalization) {
			$newRedirect = $publicData->getRedirect();
		}

		// 1. Get all blocks to be copied
		$draftBlocks = $this->getBlocksInPage($draftEm, $publicData);

		// 2. Get all blocks existing in public
		$existentBlocks = $this->getBlocksInPage($publicEm, $publicData);

		// 3. Remove blocks in 2, not in 1, remove all referencing block properties first
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
				->getPageProperties($draftData);
		
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
			$qb->delete(Entity\BlockPropertyMetadata::CN(), 'r')
					->where($qb->expr()->in('r.blockProperty', $propertyIdList))
					->getQuery()->execute();
			
			// Force to clear UoW, or #11 step will fail, 
			// as properties are still marked as existing inside EM
			$publicEm->flush();
			$publicEm->getUnitOfWork()->clear();
		}
		
		// 11. Merge all properties from 5
		foreach ($draftProperties as $property) {
			
			// Initialize the property metadata so it is copied as well
			/* @var $property Entity\BlockProperty */
			$metadata = $property->getMetadata();
			if ($metadata instanceof \Doctrine\ORM\PersistentCollection) {
				$metadata->initialize();
			}
			
			$publicEm->merge($property);
		}
		
		$draftEm->flush();
		$publicEm->flush();
		
		$pageEventArgs = new PageEventArgs();
		$pageEventArgs->setEntityManager($draftEm);
		// fixtures are using this
		$pageEventArgs->setProperty('localizationId', $publicData->getId());
		
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
		$locale = $localization->getLocale();
		$blockEntity = Entity\Abstraction\Block::CN();
		
		$dql = "SELECT b FROM $blockEntity b 
				JOIN b.placeHolder ph
				WHERE ph.localization = ?0";
		
		$blocks = $em->createQuery($dql)
				->setParameters(array($localizationId))
				->getResult();
		
		return $blocks;
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
			
			$page = $this->getPageLocalization()
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
			$publicEm->flush();

		} catch (\Exception $e) {
			
			$publicEm->getConnection()->rollBack();
			throw $e;
		}
		
		$publicEm->getConnection()->commit();
	}
	
	/**
	 * Move page and all localizations to trash
	 */
	public function delete()
	{
		// Remove any published version first
		$this->unPublish();
		
		$em = $this->getDoctrineEntityManager();
		$connection = $em->getConnection();
		$eventManager = $em->getEventManager();
		
		$pageLocalization = $this->getPageLocalization();
		$pageId = $pageLocalization->getMaster()
				->getId();
	
		// prepare audit listener for page delete
		$pageDeleteEventArgs = new PageDeleteEventArgs();
		$pageDeleteEventArgs->setPageId($pageId);
		$eventManager->dispatchEvent(AuditEvents::pagePreDeleteEvent, $pageDeleteEventArgs);
		
		$connection->beginTransaction();
		try{
			
			$pageId = $pageLocalization->getMaster()
					->getId();
			
			$page = $em->find(AbstractPage::CN(), $pageId);
		
			$localizations = $page->getLocalizations();
			foreach($localizations as $localization) {
				if ($localization instanceof PageLocalization) {
					$pathEntity = $localization->getPathEntity();
					$em->remove($pathEntity);
					
					$localization->resetPath();
				}
			}
			$em->flush();

			$em->remove($page);
 			$em->flush();
			
		} catch (\Exception $e) {
			$connection->rollBack();
			throw $e;
		}

		$connection->commit();
		
		// reset audit listener state
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
		$localizationId = $this->getPageLocalization()
				->getId();

		$dql = "SELECT ph FROM $placeHolderEntity ph 
				WHERE ph.localization = ?0";
		
		$placeHolders = $em->createQuery($dql)
				->setParameters(array($localizationId))
				->getResult();
		
		return $placeHolders;
	}
	
	/**
	 * Recursively goes through entity collections, clones and persists elements from them
	 * 
	 * @param Entity $entity
	 * @param Entity $associationOwner
	 * @param boolean $skipPathEvent
	 * @return Entity
	 */
	public function recursiveClone($entity, $associationOwner = null, $skipPathEvent = false, $targetLocale = null) 
	{
		$em = $this->getDoctrineEntityManager();
		$cloned = false;
		
		// reset at first iteration
		if (is_null($associationOwner)) {
			$this->_clonedEntities = array();
			$this->_cloneRecursionDepth = 1;
		} else {
			$this->_cloneRecursionDepth++;
		}
		
		$entityData = $em->getUnitOfWork()->getOriginalEntityData($entity);
		$classMetadata = $em->getClassMetadata($entity::CN());
		
		$entityHash = spl_object_hash($entity);
		$newEntity = null;
		
		if ( ! isset($this->_clonedEntities[$entityHash])) {
			$newEntity = clone $entity;
			$this->_clonedEntities[$entityHash] = $newEntity;
			$cloned = true;
		} else {
			$newEntity = $this->_clonedEntities[$entityHash];
		}
		
		foreach ($classMetadata->associationMappings as $fieldName => $association) {
			if ( ! $association['isOwningSide']) {
				if (isset($entityData[$fieldName])) {
					if ($entityData[$fieldName] instanceof Collection) {
						foreach ($entityData[$fieldName] as $collectionItem) {
							$this->recursiveClone($collectionItem, $newEntity, $skipPathEvent, $targetLocale);
						}
					} else {
						$this->recursiveClone($entityData[$fieldName], $newEntity, $skipPathEvent, $targetLocale);
					}
				}
			} else if ( ! is_null($associationOwner)) {
				$ownerEntityClassName = $classMetadata->associationMappings[$fieldName]['targetEntity'];
				$ownerReflectionClass = new \ReflectionClass($ownerEntityClassName);

				// FIXME association handling needs rework, not full and buggy
				if ($ownerReflectionClass->isInstance($associationOwner)) {
					$classMetadata->reflFields[$fieldName]->setValue($newEntity, $associationOwner);
					
					if ( ! $cloned) {
						$em->getUnitOfWork()->propertyChanged($newEntity, $fieldName, null, $associationOwner);
					}
				}
			}
		}
		
		// handle specific cases
		//  - copy referenced elements manually
		//  - reset path for PageLocalizations
		if ($newEntity instanceof Entity\BlockPropertyMetadata && $cloned) {
			$referencedElement = $entity->getReferencedElement();
			
			$newReferencedElement = clone $referencedElement;
//			if ($newReferencedElement instanceof LinkReferencedElement
//					&& $newReferencedElement->getResource() == LinkReferencedElement::RESOURCE_PAGE) {
//				
//				$page = $referencedElement->getPage();
//				if ($page instanceof PageLocalization) {
//					$master = $page->getMaster();
//					$correctLocalization = $em->getRepository(PageLocalization::CN())->findOneBy(array('master' => $master->getId(), 'locale' => $targetLocale));
//					if ($correctLocalization instanceof PageLocalization) {
//						$newReferencedElement->setPageId($correctLocalization->getId());
//					} else {
//						$newReferencedElement->setPageId(null);
//						$newReferencedElement->setResource(LinkReferencedElement::RESOURCE_LINK);
//						$newReferencedElement->setHref('#');
//					}
//				}
//			}
			$em->persist($newReferencedElement);
			
			$newEntity->setReferencedElement($newReferencedElement);
		}
		else if ($newEntity instanceof Entity\PageLocalization && ! $skipPathEvent) {
			$eventArgs = new LifecycleEventArgs($newEntity, $em);
			$em->getEventManager()
					->dispatchEvent(PagePathGenerator::postPageClone, $eventArgs);
		}
		
		$em->persist($newEntity);

		// workaround to keep cloned entities in sync with database
		// otherwise using them after clone will fail
		$this->_cloneRecursionDepth--;
		if ($this->_cloneRecursionDepth == 0) {
			$em->flush();
			foreach ($this->_clonedEntities as $clonedEntity) {
				$em->refresh($clonedEntity);
			}
		}
		
		return $newEntity;
	}
	
}
