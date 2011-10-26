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
		
		// Set creation time if empty
		if ($draftData instanceof Entity\PageLocalization) {
			if ( ! $draftData->isPublishTimeSet()) {
				$draftData->setCreationTime();
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
		$publicPage = $publicEm->find(PageRequest::PAGE_ABSTRACT_ENTITY, $pageId);
				
		// Something went very wrong
		if (empty($publicPage)) {
			throw new Exception\LogicException("Page {$pageId} is not found inside the public scheme");
		}
		
		$proxy = $publicEm->getProxyFactory()->getProxy(Entity\ReferencedElement\LinkReferencedElement::CN(), -1);

		// Remove the old redirect link referenced element
		$publicData = $publicPage->getLocalization($localeId);
		$oldRedirect = $newRedirect = null;

		if ($publicData instanceof Entity\PageLocalization) {
			$oldRedirect = $publicData->getRedirect();
		}
		
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
			
			// Force to clear UoW, or #11 step will fail, 
			// as properties are still marked as existing inside EM
			$publicEm->flush();
			$publicEm->getUnitOfWork()->clear();
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

		$draftEm->flush();
		$publicEm->flush();
		
		// Store page version
		$this->storeHistoredVersion();
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
	
	/**
	 * Creates copy of published page version inside history schema
	 * @param Entity\Abstraction\Localization $publicData 
	 */
	private function storeHistoredVersion()
	{
		
		$draftEm = $this->getDoctrineEntityManager();
		
		$historyEm = ObjectRepository::getEntityManager('Supra\Cms\Abstraction\History');
		$connection = $historyEm->getConnection();
		
		$connection->beginTransaction();
		try {
			$historyEm->getUnitOfWork()
					->clear();
			
			// Revision info
			$revisionData = new Entity\RevisionData();
			
			$user = $this->getUser();
			// FIXME: workaround for fixtures
			$userId = 'fixture-has-no-user';
			if ($user instanceof \Supra\User\Entity\User) {
				$userId = $user->getId();
			}
			$revisionData->setUser($userId);

			$historyEm->persist($revisionData);
			$historyEm->flush();
			
			$revisionDataId = $revisionData->getId();
			
			$pageLocalization = $this->getPageLocalization();
			$page = $pageLocalization->getMaster();
			
			// History revision listener will get revision id from entity 
			// ($historyPage will inherit it from $page)
			// when first persist operation will be performed
			$page->setRevisionId($revisionDataId);
			$historyPage = $historyEm->merge($page);
			
			$proxy = $historyEm->getProxyFactory()->getProxy(Entity\ReferencedElement\LinkReferencedElement::CN(), -1);
			
			$pageLocalization->setRevisionId($revisionDataId);
			$historyPageLocalization = $historyEm->merge($pageLocalization);
			$historyPageLocalization->setMaster($historyPage);
			
			$historyPageLocalization;

			$placeHolders = $page->getPlaceHolders();
			foreach ($placeHolders as $placeHolder) {
				$historyEm->merge($placeHolder);
			}

			$blocks = $this->getBlocksInPage($draftEm, $pageLocalization);
			foreach($blocks as $block) {
				$historyEm->merge($block);
			}
			
			$properties = $this->getBlockPropertySet()
				->getPageProperties($pageLocalization);

			foreach ($properties as $property) {
				$historyEm->merge($property);
			}

			// if page is a root template, we will copy it's layout
			if ($page instanceof Entity\Template 
					&& $page->isRoot()) {

				$layouts = $page->getTemplateLayouts();
				foreach ($layouts as $layout) {
					$historyEm->merge($layout);
				}
			}

			$historyEm->flush();

		} catch (\Exception $e) {
			
			$connection->rollBack();
			throw $e;
		}
		
		$connection->commit();
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
			
			$page = $publicEm->find(Entity\Abstraction\AbstractPage::CN(), $page->getId());
			
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
		
		$draftEm = ObjectRepository::getEntityManager('#cms');
		$trashEm = ObjectRepository::getEntityManager('#trash');

		$draftEm->getConnection()
				->beginTransaction();
		$trashEm->getConnection()
				->beginTransaction();
		
		try{
			
			$page = $this->getPageLocalization()
					->getMaster();
			
			$trashEm->merge($page);

			$placeHolders = $page->getPlaceHolders();
			foreach($placeHolders as $placeHolder) {
				$trashEm->merge($placeHolder);
				$draftEm->remove($placeHolder);
			}

			$localizationSet = $page->getLocalizations();
			foreach($localizationSet as $localization) {
				$trashEm->merge($localization);
			
				$blocks = $this->getBlocksInPage($draftEm, $localization);
				foreach($blocks as $block) {
					$trashEm->merge($block);
				}

				$properties = $this->getPageBlockProperties($draftEm, $localization);
				foreach ($properties as $property) {
					$trashEm->merge($property);
				}

				$draftEm->remove($localization);
			}
			
			if ($page instanceof Entity\Template && $page->isRoot()) {
				$templateLayouts = $page->getTemplateLayouts();
				foreach ($templateLayouts as $templateLayout) {
					$trashEm->merge($templateLayout);
					$draftEm->remove($templateLayout);
				}
			}
			
			$trashEm->flush();
			$draftEm->flush();
			
			$draftEm->getUnitOfWork()->clear();
			$page = $draftEm->find(Entity\Abstraction\AbstractPage::CN(), $page->getId());
			$draftEm->remove($page);
			$draftEm->flush();

		} catch (\Exception $e) {
			
			$trashEm->getConnection()->rollBack();
			$draftEm->getConnection()->rollBack();
			
			throw $e;
		}
		
		$trashEm->getConnection()->commit();
		$draftEm->getConnection()->commit();
		
	}
	
	/**
	 * Restores page and it localizations from trash
	 */
	public function restore()
	{
		
		$draftEm = ObjectRepository::getEntityManager('#cms');
		$trashEm = ObjectRepository::getEntityManager('#trash');

		$draftEm->getConnection()
				->beginTransaction();
		$trashEm->getConnection()
				->beginTransaction();
		
		try{
			
			$page = $this->getPageLocalization()
					->getMaster();
			
			$page = $trashEm->find(Entity\Abstraction\AbstractPage::CN(), $page->getId());
			
			$draftPage = $draftEm->merge($page);
			
			$draftEm->getRepository(Entity\Abstraction\AbstractPage::CN())
					->getNestedSetRepository()
					->add($draftPage);

			//$placeHolders = $page->getPlaceHolders();
			$placeHolders = $this->getPlaceHolders($trashEm);
			foreach($placeHolders as $placeHolder) {
				$draftEm->merge($placeHolder);
				//$trashEm->remove($placeHolder);
			}

			$localizationSet = $page->getLocalizations();
			foreach($localizationSet as $localization) {
				$draftEm->merge($localization);
			
				$blocks = $this->getBlocksInPage($trashEm, $localization);
				foreach($blocks as $block) {
					$draftEm->merge($block);
					//$trashEm->remove($block);
				}

				$properties = $this->getPageBlockProperties($trashEm, $localization);
				foreach ($properties as $property) {
					$block = $property->getBlock();
					if ( ! ($block instanceof Entity\Abstraction\Block)) {
						$blockObj = $trashEm->find(Entity\Abstraction\Block::CN(), $block);
						
						if ( ! ($blockObj instanceof Entity\Abstraction\Block)) {
							$blockObj = $draftEm->find(Entity\Abstraction\Block::CN(), $block);
							
							if ( ! ($blockObj instanceof Entity\Abstraction\Block)) {
								// skip properties without existing blocks
								// this may happen when property is inherited from
								// template block and this block was removed 
								continue;
							}
						}

						$property->setBlock($blockObj);
					}
					
					$draftEm->merge($property);
					//$trashEm->remove($property);
				}

				$trashEm->remove($localization);
				
				$blocksToRemove = Entity\Abstraction\Entity::collectIds($blocks);
				if ( ! empty($blocksToRemove)) {
					$qb = $trashEm->createQueryBuilder();
					$qb->delete(Entity\Abstraction\Block::CN(), 'b')
							->where($qb->expr()->in('b.id', $blocksToRemove))
							->getQuery()->execute();
				}
			}
			
			if ($page instanceof Entity\Template && $page->isRoot()) {
				$templateLayouts = $page->getTemplateLayouts();
				foreach ($templateLayouts as $templateLayout) {
					//ATTENTION: We will not restore template layouts, as, currently, 
					//it is impossible to restore template as root template
					//$draftEm->merge($templateLayout);
					$trashEm->remove($templateLayout);
				}
			}
			
			$draftEm->flush();
			
			$placeHoldersToRemove = Entity\Abstraction\Entity::collectIds($placeHolders);
				if ( ! empty($placeHoldersToRemove)) {
				$qb = $trashEm->createQueryBuilder();
				$qb->delete(Entity\Abstraction\PlaceHolder::CN(), 'ph')
						->where($qb->expr()->in('ph.id', $placeHoldersToRemove))
						->getQuery()->execute();
			}
					
			// need to flush before cleaning UoW
			$trashEm->flush();
			
			$trashEm->getUnitOfWork()->clear();
			$page = $trashEm->find(Entity\Abstraction\AbstractPage::CN(), $page->getId());
			$trashEm->remove($page);
			$trashEm->flush();

		} catch (\Exception $e) {
			
			$trashEm->getConnection()->rollBack();
			$draftEm->getConnection()->rollBack();
			
			throw $e;
		}
		
		$trashEm->getConnection()->commit();
		$draftEm->getConnection()->commit();
		
		return $draftPage;
			
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
		$masterId = $this->getPageLocalization()
				->getMaster()->getId();

		$dql = "SELECT ph FROM $placeHolderEntity ph 
				WHERE ph.master = ?0";
		
		$placeHolders = $em->createQuery($dql)
				->setParameters(array($masterId))
				->getResult();
		
		return $placeHolders;
	}
	
}
