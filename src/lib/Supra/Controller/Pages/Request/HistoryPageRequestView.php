<?php

namespace Supra\Controller\Pages\Request;

use Supra\Database\Doctrine\Hydrator\ColumnHydrator;
use Doctrine\ORM\Query;
use Supra\Controller\Pages\Entity;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Exception;
use Supra\ObjectRepository\ObjectRepository;

use Supra\Controller\Pages\Set;

/**
 * Request object for history mode requests
 */
class HistoryPageRequestView extends PageRequest
{
	/**
	 * Contains revision id string
	 * @var string
	 */
	protected $revision;
	
	/**
	 * @param string $revision 
	 */
	public function setRevision($revision) {
		$this->revision = $revision;
	}

	public function getPlaceHolderSet()
	{
		if (isset($this->placeHolderSet)) {
			return $this->placeHolderSet;
		}
		
		$page = $this->getPage();
		$localization = $this->getPageLocalization();
		$this->placeHolderSet = new Set\PlaceHolderSet($localization);
		
		$draftEm = ObjectRepository::getEntityManager('Supra\Cms');
		$pageSetIds = null;
		
		if ($page instanceof Entity\Template) {
			
			$layout = null;
			
			if ( ! $page->isRoot()) {
				$localizationId = $this->getPageLocalization()->getId();
				$draftEm->getUnitOfWork()->clear();
				$localization = $draftEm->find(static::TEMPLATE_DATA_ENTITY, $localizationId);

				$pageSetIds = $localization->getTemplateHierarchy()->collectIds();
				$layout = $localization->getTemplateHierarchy()
											->getRootTemplate()
											->getLayout();
			} else {
				$pageSetIds = array($page->getId());
				$layout = $page->getLayout();
			}
			$this->overrideLayout($layout);
		} else {
			
			$pageSetIds = $this->getPageSetIds();
		}
		
		$layoutPlaceHolderNames = $this->getLayoutPlaceHolderNames();
		
		if (empty($pageSetIds) || empty($layoutPlaceHolderNames)) {

			return $this->placeHolderSet;
		}
		
		// Load template placeholders from draft
		$qb = $draftEm->createQueryBuilder();
		$qb->select('ph')
				->from(static::PLACE_HOLDER_ENTITY, 'ph')
				->join('ph.localization', 'pl')
				->join('pl.master', 'p')
				->where($qb->expr()->in('ph.name', $layoutPlaceHolderNames))
				->andWhere($qb->expr()->in('p.id', $pageSetIds))
				->andWhere($qb->expr()->eq('ph.type', '0'))
				->addOrderBy('p.level', 'ASC');

		$query = $qb->getQuery();
		$draftPlaceHolderArray = $query->getResult();

		foreach ($draftPlaceHolderArray as $placeHolder) {
			$this->placeHolderSet->append($placeHolder);
		}

		// Current page placeholders
		$pagePlaceholders = $localization->getPlaceHolders();
		
		foreach($pagePlaceholders as $placeHolder) {
			$this->placeHolderSet->append($placeHolder);
		}

		return $this->placeHolderSet;
	}
	
	/**
	 * @return Set\BlockSet
	 */
	public function getBlockSet()
	{
		if (isset($this->blockSet)) {
			return $this->blockSet;
		}
		
		// History entity manager
		$em = $this->getDoctrineEntityManager();
		$this->blockSet = new Set\BlockSet();
		
		$placeHolderSet = $this->getPlaceHolderSet();

		$finalPlaceHolderIds = $placeHolderSet->getFinalPlaceHolders()
				->collectIds();

		$parentPlaceHolderIds = $placeHolderSet->getParentPlaceHolders()
				->collectIds();

		if (empty($finalPlaceHolderIds) && empty($parentPlaceHolderIds)) {

			return $this->blockSet;
		}
		
		// History schema
		// locale isn't used as it is enough to use only revision id
		$qb = $em->createQueryBuilder();
		$qb->select('b')
				->from(static::BLOCK_ENTITY, 'b')
				->join('b.placeHolder', 'ph')
				->where($qb->expr()->in('ph.id', $finalPlaceHolderIds))
				//->andWhere('b.locale = ?0 AND b.revision = ?1')
				->andWhere('b.revision = ?0')
				->orderBy('b.position', 'ASC');

		$query = $qb->getQuery();
		//$query->execute(array($this->getLocale(), $this->revision));
		$query->execute(array($this->revision));
		$blocks = $query->getResult();
		
		// Draft connection
		$em = ObjectRepository::getEntityManager('Supra\Cms');
		$qb = $em->createQueryBuilder();
		$qb->select('b')
				->from(static::BLOCK_ENTITY, 'b')
				->join('b.placeHolder', 'ph')
				->orderBy('b.position', 'ASC');
		$expr = $qb->expr();
		$or = $expr->orX();
		
		if ( ! empty($finalPlaceHolderIds)) {
			$or->add($expr->in('ph.id', $finalPlaceHolderIds));
		}

		if ( ! empty($parentPlaceHolderIds)) {
			$lockedBlocksCondition = $expr->andX(
					$expr->in('ph.id', $parentPlaceHolderIds),
					'b.locked = TRUE'
			);
			$or->add($lockedBlocksCondition);
		}

		$and = $expr->andX();
		$and->add($or);

		$qb->where($and);
		$qb->andWhere('ph.type = 0 AND b.locale = ?0');
		
		$query = $qb->getQuery();
		$query->execute(array($this->getLocale()));
		$draftBlocks = $query->getResult();
		
		$missingBlocks = array_diff($draftBlocks, $blocks);
		if ( ! empty ($missingBlocks)) {
		
			$page = $this->getPage();
			foreach($missingBlocks as $key => $block) {
				/* @var $block Entity\Abstraction\Block */
				
				$master = $block->getPlaceHolder()->getMaster()->getMaster();
				if ($page->equals($master)) {
					unset($missingBlocks[$key]);
				}
			}
			$blocks = array_merge($missingBlocks, $blocks);
		}

		foreach ($blocks as $block) {
			if ($block->inPlaceHolder($parentPlaceHolderIds)) {
				$this->blockSet[] = $block;
			}
		}

		foreach ($blocks as $block) {
			if ($block->inPlaceHolder($finalPlaceHolderIds)) {
				$this->blockSet[] = $block;
			}
		}
		
		return $this->blockSet;
	}
	
	/**
	 * @return Set\BlockPropertySet
	 */
	public function getBlockPropertySet($skipPublic = false)
	{
		if (isset($this->blockPropertySet)) {
			return $this->blockPropertySet;
		}
		
		$this->blockPropertySet = new Set\BlockPropertySet();
		
		// History em
		$em = $this->getDoctrineEntityManager();
		$draftEm = ObjectRepository::getEntityManager('Supra\Cms');
		
		$qb = $em->createQueryBuilder();
		$expr = $qb->expr();
		$or = $expr->orX();

		$cnt = 0;
		$blockSet = $this->getBlockSet();
		$page = $this->getPage();
		
		$blockSetIds = Entity\Abstraction\Entity::collectIds($blockSet);

		foreach ($blockSet as $block) {
			/* @var $block Entity\Abstraction\Block */
			
			$master = null;
			
			if ($block->getLocked()) {
				$master = $block->getPlaceHolder()
						->getMaster()
						->getMaster();
			} else {
				$master = $page;
			}
			
			\Log::debug("Master node for {$block} is found - {$master}");
			
			// FIXME: n+1 problem
			$data = $master->getLocalization($this->getLocale());
			
			if (empty($data)) {
				\Log::warn("The data record has not been found for page {$master} locale {$this->locale}, will not fill block parameters");
				$blockSet->removeInvalidBlock($block, "Page data for locale not found");
				continue;
			}

			$blockId = $block->getId();
			$dataId = $data->getId();

			$and = $expr->andX();
			$and->add($expr->eq('bp.block', '?' . (++$cnt)));
			$qb->setParameter($cnt, $blockId);
			$and->add($expr->eq('bp.localization', '?' . (++$cnt)));
			$qb->setParameter($cnt, $dataId);

			$or->add($and);
			\Log::debug("Have generated condition for properties fetch for block $block");
		}

		$qb->select('bp')
				->from(static::BLOCK_PROPERTY_ENTITY, 'bp')
				->where($or)
				->andWhere('bp.revision = :revision');

		$query = $qb->getQuery();
		$query->execute(array('revision' => $this->revision));
		$result = $query->getResult();
		
		/**
		 * If properties were loaded from _history schema, then they `block`
		 * property will contain id string (see DB Type Block) instead of Block object
		 * we must manually fill it with object from repository.
		 * TODO: is there is possible to assign proxy instead of object, to provide lazy loading?
		 */
		foreach($result as $key => $blockProperty) {
			$block = $blockProperty->getBlock();
		
			/**
			 * If block is not an object, 
			 * then we will try to load it from history schema
			 */
			if ( ! ($block instanceof Entity\Abstraction\Block)) {
				
				$blockObj = $em->find(static::BLOCK_ENTITY, array('id' => $block, 'revision' => $this->revision));
				
				/**
				 * If nothing were found inside `_history` schema, 
				 * then this property is inherited from template block, and we should search
				 * for them inside _draft tables
				 */
				if ( ! ($blockObj instanceof Entity\Abstraction\Block)) {
					
					$blockObj = $draftEm->find(static::BLOCK_ENTITY, array('id' => $block));
					if ( ! ($blockObj instanceof Entity\Abstraction\Block)) {
						
						//throw new \Exception('Block not found');
						unset($result[$key]);
					}
				}
				$blockProperty->setBlock($blockObj);
			}
		}
		
		$qb = $draftEm->createQueryBuilder();
		$expr = $qb->expr();
		$or = $expr->orX();

		$cnt = 0;
		foreach ($blockSet as $block) {
			/* @var $block Entity\Abstraction\Block */
			
			$master = null;

			if ($block->getLocked()) {
				$master = $block->getPlaceHolder()
						->getMaster()
						->getMaster();
			} else {
				$master = $page;
			}

			if ($master->equals($page)) {
				continue;
			}

			\Log::debug("Master node for {$block} is found - {$master}");

			// FIXME: n+1 problem
			$data = $master->getLocalization($this->getLocale());

			if (empty($data)) {
				\Log::warn("The data record has not been found for page {$master} locale {$this->getLocale()}, will not fill block parameters");
				$blockSet->removeInvalidBlock($block, "Page data for locale not found");
				continue;
			}

			$blockId = $block->getId();
			$dataId = $data->getId();

			$and = $expr->andX();
			$and->add($expr->eq('bp.block', '?' . (++$cnt)));
			$qb->setParameter($cnt, $blockId);
			$and->add($expr->eq('bp.localization', '?' . (++$cnt)));
			$qb->setParameter($cnt, $dataId);

			$or->add($and);
			\Log::debug("Have generated condition for properties fetch for block $block");
		}

		if ($cnt > 0) {
			$qb->select('bp')
					->from(static::BLOCK_PROPERTY_ENTITY, 'bp')
					->where($or);

			$query = $qb->getQuery();
			\Log::debug("Running query {$qb->getDQL()} to find block properties");
			$draftProperties = $query->getResult();

			$missingProperties = array_diff($draftProperties, $result);
			$result = array_merge($result, $missingProperties);
		}
		
		$this->blockPropertySet->exchangeArray($result);
		
		return $this->blockPropertySet;
	}
	
	public function restore($destinationEm)
	{
		$em = $this->getDoctrineEntityManager();
		$page = $this->getPage();
		
		$destPage = $destinationEm->merge($page);
		
		$pageLocalization = $this->getPageLocalization();
		$destLocalization = $destinationEm->merge($pageLocalization);
		
		// place holders
		$placeHolders = $pageLocalization->getPlaceHolders();
		foreach ($placeHolders as $placeHolder) {
			$destinationEm->merge($placeHolder);
		}
		
		// blocks
		$existingBlocks = $this->getBlocksInPage($destinationEm, $destLocalization);
		$blocks = $this->getBlockSet();
		foreach($blocks as $block) {
			$destinationEm->merge($block);
		}
		
		// block properties
		$trashProperties = $this->getBlockPropertySet()
				->getPageProperties($pageLocalization);
		foreach ($trashProperties as $property) {
			$destinationEm->merge($property);
		}
		
		// Collect history block property IDs
		$trashPropertyIds = Entity\Abstraction\Entity::collectIds($trashProperties);
		
		// Collect draft block property IDs
		$draftProperties = $this->getPageBlockProperties($destinationEm);
		$draftPropertyIds = Entity\Abstraction\Entity::collectIds($draftProperties);

		// Calculate removed properties
		$removedPropertyIds = array_diff($draftPropertyIds, $trashPropertyIds);
		// ...delete their metadata
		if ( ! empty($removedPropertyIds)) {
			$qb = $destinationEm->createQueryBuilder();
			$qb->delete(Entity\BlockPropertyMetadata::CN(), 'r')
					->where($qb->expr()->in('r.blockProperty', $removedPropertyIds))
					->getQuery()->execute();
		}
		
		// ...and properties itself
		if ( ! empty($removedPropertyIds)) {
			$qb = $destinationEm->createQueryBuilder();
			$qb->delete(Entity\BlockProperty::CN(), 'bp')
					->where($qb->expr()->in('bp.id', $removedPropertyIds))
					->getQuery()->execute();
		}
		
		// Find un-used blocks and remove them from draft
		$existingBlockIds = Entity\Abstraction\Entity::collectIds($existingBlocks);
		$blocksIds = Entity\Abstraction\Entity::collectIds($blocks);
		$blocksToRemove = array_diff($existingBlockIds, $blocksIds);
		
		if ( ! empty($blocksToRemove)) {
			$qb = $destinationEm->createQueryBuilder();
			$qb->delete(Entity\Abstraction\Block::CN(), 'b')
					->where($qb->expr()->in('b.id', $blocksToRemove))
					->getQuery()->execute();
		}
		
		if ($page instanceof Entity\Template 
				&& $page->isRoot()) {
				
			$layouts = $page->getTemplateLayouts();
			foreach ($layouts as $layout) {
				$destinationEm->merge($layout);
			}
		}
		
		$listeners = $destinationEm->getEventManager()->getListeners(\Doctrine\ORM\Events::onFlush);
		foreach ($listeners as $listener) {
			if ($listener instanceof \Supra\Controller\Pages\Listener\PagePathGenerator) {
				$listeners = $destinationEm->getEventManager()->removeEventListener(\Doctrine\ORM\Events::onFlush, $listener);
			}
		}
		
		$destinationEm->flush();
		$em->flush();
	}
	
	private function getBlocksInPage($em)
	{
		$localizationId = $this->getPageLocalization()->getId();
		$locale = $this->getLocale();
		$blockEntity = PageRequest::BLOCK_ENTITY;
		
		$dql = "SELECT b FROM $blockEntity b 
				JOIN b.placeHolder ph
				WHERE ph.localization = ?0 AND b.locale = ?1";
		
		$blocks = $em->createQuery($dql)
				->setParameters(array($localizationId, $locale))
				->getResult();
		
		return $blocks;
	}
	
	private function getPageBlockProperties($em)
	{
		$localizationId = $this->getPageLocalization()->getId();
		$propertyEntity = PageRequest::BLOCK_PROPERTY_ENTITY;
		
		$dql = "SELECT bp FROM $propertyEntity bp 
				WHERE bp.localization = ?0";
		
		$properties = $em->createQuery($dql)
				->setParameters(array($localizationId))
				->getResult();
		
		return $properties;
	}
		
}
