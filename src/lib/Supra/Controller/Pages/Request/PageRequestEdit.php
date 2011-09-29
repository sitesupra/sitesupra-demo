<?php

namespace Supra\Controller\Pages\Request;

use Supra\Database\Doctrine\Hydrator\ColumnHydrator;
use Doctrine\ORM\Query;
use Supra\Controller\Pages\Entity;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Exception;
use Supra\ObjectRepository\ObjectRepository;

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
	 * @param EntityManager $publicEm
	 */
	public function publish(EntityManager $publicEm)
	{
		$draftEm = $this->getDoctrineEntityManager();
		
		if ($draftEm == $publicEm) {
			$this->log->debug("Publish doesn't do anything because CMS and public database connections are identical");
			
			return;
		}
		
		$draftData = $this->getPageLocalization();
		
		$pageId = $draftData->getMaster()->getId();
		$localeId = $draftData->getLocale();
//		$pageDataId = $draftData->getId();

		$draftPage = $draftData->getMaster();
		
		/*
		 * NB!
		 * This is important to load the public page first before merging the 
		 * data into the public scheme because doctrine will create abstract not
		 * usable proxy class for it otherwise
		 */
		/* @var $publicPage Entity\Abstraction\AbstractPage */
		$publicPage = $publicEm->find(PageRequest::PAGE_ABSTRACT_ENTITY, $pageId);
		
		// Something went very wrong
		if (empty($publicPage)) {
			throw new Exception\LogicException("Page {$pageId} is not found inside the public scheme");
		}
		
		// Remove the old redirect link referenced element
		$publicData = $publicPage->getLocalization($localeId);
		$oldRedirect = $newRedirect = null;

		// If there are something published, then copy it to _history
		if ($publicData instanceof Entity\Abstraction\Localization) {
			$historyEm = ObjectRepository::getEntityManager('Supra\Cms\Abstraction\History');
		
			// TODO: remove PagePathGenerator listener from commons and remove this code (also in page delete/restore methods)
			$listeners = $historyEm->getEventManager()->getListeners(\Doctrine\ORM\Events::onFlush);
			foreach ($listeners as $listener) {
				if ($listener instanceof \Supra\Controller\Pages\Listener\PagePathGenerator) {
					$listeners = $historyEm->getEventManager()->removeEventListener(\Doctrine\ORM\Events::onFlush, $listener);
				}
			}
			
			$revisionData = new Entity\RevisionData();
			
			$userName = $this->getUser()
					->getName();
			$revisionData->setUser($userName);
			$historyEm->persist($revisionData);
			
			$revisionId = $revisionData->getId();
			$historyEm->getEventManager()->addEventListener(\Doctrine\ORM\Events::prePersist, new \Supra\Controller\Pages\Listener\HistoryRevision($revisionId));

			$historyPage = $historyEm->merge($publicPage);
			
			// FIXME: this just registers link referenced element proxy class inside the metadata or else merge fails..
			$proxy = $historyEm->getProxyFactory()->getProxy(Entity\ReferencedElement\LinkReferencedElement::CN(), -1);
			$historyData = $historyEm->merge($publicData);
			
			$publicPlaceholders = $publicPage->getPlaceHolders();
			foreach ($publicPlaceholders as $placeholder) {
				$historyEm->merge($placeholder);
			}
			
			$publicBlocks = $this->getBlocksInPage($publicEm, $publicData);
			foreach($publicBlocks as $block) {
				$historyEm->merge($block);
			}
			
			$publicProperties = $this->getBlockPropertySet()
				->getPageProperties($publicData);
			foreach ($publicProperties as $property) {
				$historyEm->merge($property);
			}
			
			$historyEm->flush();
		}
			
		if ($publicData instanceof Entity\PageLocalization) {
			$oldRedirect = $publicData->getRedirect();
		}
		
		// FIXME: this just registers link referenced element proxy class inside the metadata or else merge fails..
		$proxy = $publicEm->getProxyFactory()->getProxy(Entity\ReferencedElement\LinkReferencedElement::CN(), -1);

		// Merge the data element
		$publicData = $publicEm->merge($draftData);
		$publicData->setMaster($publicPage);
		
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
			$this->removeBlocks($publicEm, $removedBlockIdList);
		}

		// 4. Merge all placeholders, don't delete not used, let's keep them
		foreach ($draftBlocks as $block) {
			$placeHolder = $block->getPlaceHolder();
			$publicEm->merge($placeHolder);
		}
		
		/*
		 * For some reason in some cases Doctrine couldn't insert block because 
		 * placeholder wasn't yet created
		 */
		$publicEm->flush();

		// 5. Merge all blocks in 1
		foreach ($draftBlocks as $block) {
			$publicEm->merge($block);
		}

		// 6. Get properties to be copied (of a. self and b. template)
		$draftProperties = $this->getBlockPropertySet()
				->getPageProperties($draftData);
		
		// 7. For properties 5b get block, placeholder IDs, check their existance in public, get not existant
		$missingBlockIdList = array();

		// Searching for missing parent template blocks IDs in the public schema
		$blockIdList = $draftProperties->getBlockIdList();
		$parentTemplateBlockIdList = array_diff($blockIdList, $draftBlockIdList);
		
		if ( ! empty($parentTemplateBlockIdList)) {
			$blockEntity = PageRequest::BLOCK_ENTITY;

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
		$publicPlaceHolderIdList = $this->loadEntitiesByIdList($publicEm, PageRequest::PLACE_HOLDER_ENTITY, $draftPlaceHolderIdList, 'e.id', ColumnHydrator::HYDRATOR_ID);
		$missingPlaceHolderIdList = array_diff($draftPlaceHolderIdList, $publicPlaceHolderIdList);

		$missingPlaceHolders = $this->loadEntitiesByIdList($draftEm, PageRequest::PLACE_HOLDER_ENTITY, $missingPlaceHolderIdList);

		/* @var $placeHolder Entity\Abstraction\PlaceHolder */
		foreach ($missingPlaceHolders as $placeHolder) {
			$placeHolder = $publicEm->merge($placeHolder);

			// Reset locked property
			if ($placeHolder instanceof Entity\TemplatePlaceHolder) {
				$placeHolder->setLocked(false);
			}
		}

		// 9. Merge missing blocks (add $temporary property)
		$missingBlocks = $this->loadEntitiesByIdList($draftEm, PageRequest::TEMPLATE_BLOCK_ENTITY, $missingBlockIdList);

		/* @var $block Entity\TemplateBlock */
		foreach ($missingBlocks as $block) {
			$block = $publicEm->merge($block);
			$block->setTemporary(true);
		}

		// 10. Clear all property metadata on the public going to be merged
		//TODO: remove reference elements as well!
		$propertyIdList = $draftProperties->collectIds();
		
		if ( ! empty($propertyIdList)) {
			$qb = $publicEm->createQueryBuilder();
			$qb->delete(Entity\BlockPropertyMetadata::CN(), 'r')
					->where($qb->expr()->in('r.blockProperty', $propertyIdList))
					->getQuery()->execute();
		}
		
		// 11. Merge all properties from 5
		foreach ($draftProperties as $property) {
			$publicEm->merge($property);
		}
		
		// 12. Remove old redirect if exists and doesn't match with new one
		if ( ! is_null($oldRedirect)) {
			if (is_null($newRedirect) || ! $oldRedirect->equals($newRedirect)) {
				$publicEm->remove($oldRedirect);
			}
		}

		$publicEm->flush();
	}
	
	/**
	 * Loads blocks from the current page
	 * @param EntityManager $em
	 * @param Entity\Abstraction\Localization $data
	 * @return array 
	 */
	private function getBlocksInPage(EntityManager $em, Entity\Abstraction\Localization $data)
	{
		$masterId = $data->getMaster()->getId();
		$locale = $data->getLocale();
		$blockEntity = PageRequest::BLOCK_ENTITY;
		
		$dql = "SELECT b FROM $blockEntity b 
				JOIN b.placeHolder p
				WHERE p.master = ?0 AND b.locale = ?1";
		
		$blocks = $em->createQuery($dql)
				->setParameters(array($masterId, $locale))
				->getResult();
		
		return $blocks;
	}
	
	/**
	 * Removes blocks with all properties by ID
	 * @param EntityManager $em
	 * @param array $blockIdList
	 */
	private function removeBlocks(EntityManager $em, array $blockIdList)
	{
		if (empty($blockIdList)) {
			return;
		}
		
		$blockPropetyEntity = PageRequest::BLOCK_PROPERTY_ENTITY;
		$blockEntity = PageRequest::BLOCK_ENTITY;
		
		$qb = $em->createQueryBuilder();
		$qb->delete($blockPropetyEntity, 'p')
				->where($qb->expr()->in('p.block', $blockIdList))
				->getQuery()
				->execute();
		
		$qb = $em->createQueryBuilder();
		$qb->delete($blockEntity, 'b')
				->where($qb->expr()->in('b', $blockIdList))
				->getQuery()
				->execute();
	}
	
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
		$qb->from(PageRequest::BLOCK_ENTITY, 'b')
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
	
	private function getPagePropertySet (EntityManager $em, Entity\Abstraction\Localization $data)
	{
		$pageDataId = $data->getId();
		$locale = $data->getLocale();
		$propertyEntity = PageRequest::BLOCK_PROPERTY_ENTITY;
		
		$dql = "SELECT p FROM $propertyEntity p 
				WHERE p.localization = ?0";
		
		$properties = $em->createQuery($dql)
				->setParameters(array($pageDataId))
				->getResult();
		
		return $properties;
	}
	
	/**
	 * Moves page abstract entity between specified entity managers
	 * by creating a copy of page in destination manager 
	 * and removes it in source manages.
	 * Will return instance of copied page
	 * 
	 * @param EntityManager $sourceEm
	 * @param EntityManager $destEm
	 * @param integer $pageDataId
	 * @param boolean $forceSave
	 * @return type 
	 */
	public function moveBetweenManagers(EntityManager $sourceEm, EntityManager $destEm, $pageDataId, $forceSave = false)
	{
		$sourcePageLocalization = $sourceEm->find(Entity\Abstraction\Localization::CN(), $pageDataId);
		
		if ( ! ($sourcePageLocalization instanceof Entity\Abstraction\Localization)) {
			throw new Exception\RuntimeException('Specified page was not found in source repository');
		}
		
		$sourcePage = $sourcePageLocalization->getMaster();
		
		/* 
		 * Remove pathGenerator 
		 * for destination entity manager 
		 */
		$listeners = $destEm->getEventManager()->getListeners(\Doctrine\ORM\Events::onFlush);
		foreach ($listeners as $listener) {
			if ($listener instanceof \Supra\Controller\Pages\Listener\PagePathGenerator) {
				$listeners = $destEm->getEventManager()->removeEventListener(\Doctrine\ORM\Events::onFlush, $listener);
			}
		}
		
		$sourceEm->getConnection()
				->beginTransaction();
		$destEm->getConnection()
				->beginTransaction();
		
		try {
			$destPage = $destEm->merge($sourcePage);
			$destEm->flush();
			
			$pageCollection = $sourcePage->getLocalizations();
			foreach ($pageCollection as $pageLocalization) {
				
				// is needed for getBlocksInPage
				$this->setLocale($pageLocalization->getLocale());
	
				// merge page localization
				$destPageLocalization = $destEm->merge($pageLocalization);
				// merge blocks
				$blocks = $this->getBlocksInPage($sourceEm, $pageLocalization);
				foreach ($blocks as $block) {
					$placeholder = $block->getPlaceHolder();
					$destEm->merge($placeholder);
					$destEm->merge($block);
					//$sourceEm->remove($block);
				}
				// merge block properties
				$blockIdList = Entity\Abstraction\Entity::collectIds($blocks);
				$properties = $this->getPagePropertySet($sourceEm, $pageLocalization);
				foreach ($properties as $property) {
					if (in_array($property->getBlock()->getId(), $blockIdList)) {
						$destEm->merge($property);
					}
					$sourceEm->remove($property);
					$sourceEm->flush();
				}
				
				$sourceEm->remove($pageLocalization);
			}
					
			$placeholders = $sourcePage->getPlaceHolders();
			foreach($placeholders as $placeholder) {
				$destEm->merge($placeholder);
				$sourceEm->remove($placeholder);
			}
			
			$sourceEm->flush();
			$destEm->flush();

			if ( ! $forceSave) {
				$sourceEm->remove($sourcePage);
				$sourceEm->flush();
			}
			
			$destEm->getConnection()->commit();
			$sourceEm->getConnection()->commit();
		
			return $destPage;
			
		} catch (\Exception $e) {
			
			$destEm->getConnection()
					->rollBack();
			$sourceEm->getConnection()
					->rollBack();
			
			throw $e;
        }
	}
}
