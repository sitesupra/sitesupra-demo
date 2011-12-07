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
	 * @var array
	 */
	protected $pageLocalizations;
	
	/**
	 * @param string $revision 
	 */
	public function setRevision($revision) {
		$this->revision = $revision;
	}
	
	public function getPageDraftLocalizations()
	{
		if (isset($this->pageLocalizations)) {
			return $this->pageLocalizations;
		}
		
		$pageId = $this->getPage()
				->getId();
		
		$draftEm = ObjectRepository::getEntityManager(PageController::SCHEMA_DRAFT);
		$this->pageLocalizations = $draftEm->getRepository(Localization::CN())
				->findBy(array('master' => $pageId));
		
		return $this->pageLocalizations;
	}
	
	public function getDraftLocalization($localeId)
	{
		$pageLocalizations = $this->getPageDraftLocalizations();
		foreach ($pageLocalizations as $pageLocalization) {
			/* @var $pageLocalization Localization */ 
			if ($pageLocalization->getLocale() == $localeId) {
				return $pageLocalization;
			}
		}
	}
	
	public function getPageSet()
	{
		if (isset($this->pageSet)) {
			return $this->pageSet;
		}

		// Override nested set repository EM, page set will be loaded from draft
		$page = $this->getPage();
		$nestedSetRepository = $page->getNestedSetNode()
				->getRepository();
		
		$draftEm = ObjectRepository::getEntityManager(PageController::SCHEMA_DRAFT);
		$nestedSetRepository->setEntityManager($draftEm);
		
		
		$this->pageSet = $this->getPageLocalization()
				->getTemplateHierarchy();

		return $this->pageSet;
	}
	
	public function getPlaceHolderSet()
	{
		if (isset($this->placeHolderSet)) {
			return $this->placeHolderSet;
		}
		
		$page = $this->getPage();
		$localization = $this->getPageLocalization();
		$this->placeHolderSet = new Set\PlaceHolderSet($localization);
		$localeId = $localization->getLocale();
		
		$pageSetIds = $this->getPageSetIds();
		$layoutPlaceHolderNames = $this->getLayoutPlaceHolderNames();
		
		if (empty($pageSetIds) || empty($layoutPlaceHolderNames)) {

			return $this->placeHolderSet;
		}
		
		$draftEm = ObjectRepository::getEntityManager(PageController::SCHEMA_DRAFT);
		// Load template placeholders from draft
		$qb = $draftEm->createQueryBuilder();
		$qb->select('ph')
				->from(static::PLACE_HOLDER_ENTITY, 'ph')
				->join('ph.localization', 'pl')
				->join('pl.master', 'p')
				->where($qb->expr()->in('ph.name', $layoutPlaceHolderNames))
				->andWhere($qb->expr()->in('p.id', $pageSetIds))
				->andWhere('pl.locale = ?0')
				->setParameter(0, $localeId)
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
		
		// locale isn't used as it is enough to use only revision id
		$qb = $em->createQueryBuilder();
		$qb->select('b')
				->from(static::BLOCK_ENTITY, 'b')
				->join('b.placeHolder', 'ph')
				->where($qb->expr()->in('ph.id', $finalPlaceHolderIds))
				//->andWhere('b.revision = ?1')
				->andWhere('b.revision = ?0')
				->orderBy('b.position', 'ASC');

		$query = $qb->getQuery();
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
		$qb->andWhere('ph.type = 0');
		
		$query = $qb->getQuery();
		$query->execute();
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
	
	/**
	 * Does the history localization version restoration
	 */
	public function restoreLocalization()
	{
		$draftEm = ObjectRepository::getEntityManager(PageController::SCHEMA_DRAFT);
		
		// EntityRevisionListener is also subscribed
		$draftEm->getEventManager()
				->dispatchEvent(AuditEvents::pagePreRestoreEvent);
		
		$page = $this->getPage();
		
		$destPage = $draftEm->merge($page);
		
		$pageLocalization = $this->getPageLocalization();
		$destLocalization = $draftEm->merge($pageLocalization);
		
		// place holders
		$placeHolders = $pageLocalization->getPlaceHolders();
		foreach ($placeHolders as $placeHolder) {
			$draftEm->merge($placeHolder);
		}
		
		// blocks
		$existingBlocks = $this->getBlocksInPage($draftEm, $destLocalization);
		$blocks = $this->getBlockSet();
		foreach($blocks as $block) {
			$draftEm->merge($block);
		}
		
		// block properties
		$historyProperties = $this->getBlockPropertySet()
				->getPageProperties($pageLocalization);
		foreach ($historyProperties as $property) {
			$draftEm->merge($property);
		}
		
		// Collect history block property IDs
		$historyPropertyIds = Entity\Abstraction\Entity::collectIds($historyProperties);
		
		// Collect draft block property IDs
		$draftProperties = $this->getPageBlockProperties($draftEm);
		$draftPropertyIds = Entity\Abstraction\Entity::collectIds($draftProperties);

		// Calculate removed properties
		$removedPropertyIds = array_diff($draftPropertyIds, $historyPropertyIds);
		// ...delete their metadata
		if ( ! empty($removedPropertyIds)) {
			$qb = $draftEm->createQueryBuilder();
			$qb->delete(Entity\BlockPropertyMetadata::CN(), 'r')
					->where($qb->expr()->in('r.blockProperty', $removedPropertyIds))
					->getQuery()->execute();
		}
		
		// ...and properties itself
		if ( ! empty($removedPropertyIds)) {
			$qb = $draftEm->createQueryBuilder();
			$qb->delete(Entity\BlockProperty::CN(), 'bp')
					->where($qb->expr()->in('bp.id', $removedPropertyIds))
					->getQuery()->execute();
		}
		
		// Find un-used blocks and remove them from draft
		$existingBlockIds = Entity\Abstraction\Entity::collectIds($existingBlocks);
		$blocksIds = Entity\Abstraction\Entity::collectIds($blocks);
		$blocksToRemove = array_diff($existingBlockIds, $blocksIds);
		
		if ( ! empty($blocksToRemove)) {
			$qb = $draftEm->createQueryBuilder();
			$qb->delete(Entity\Abstraction\Block::CN(), 'b')
					->where($qb->expr()->in('b.id', $blocksToRemove))
					->getQuery()->execute();
		}
		
		if ($page instanceof Entity\Template 
				&& $page->isRoot()) {
				
			$layouts = $page->getTemplateLayouts();
			foreach ($layouts as $layout) {
				$draftEm->merge($layout);
			}
		}
		
		$listeners = $draftEm->getEventManager()->getListeners(\Doctrine\ORM\Events::onFlush);
		foreach ($listeners as $listener) {
			if ($listener instanceof \Supra\Controller\Pages\Listener\PagePathGenerator) {
				$listeners = $draftEm->getEventManager()->removeEventListener(\Doctrine\ORM\Events::onFlush, $listener);
			}
		}
		
		$draftEm->flush();

		$draftEm->getEventManager()
				->dispatchEvent(AuditEvents::pagePostRestoreEvent);
	
	}
	
	/**
	 * Does the full trash page version restoration (incl. available localizations)
	 */
	public function restorePage()
	{
 		$draftEm = ObjectRepository::getEntityManager(PageController::SCHEMA_DRAFT);
		$auditEm = ObjectRepository::getEntityManager(PageController::SCHEMA_AUDIT);

		$draftEventManager = $draftEm->getEventManager();
		$draftEventManager->dispatchEvent(AuditEvents::pagePreRestoreEvent);
		
		$page = $this->getPageLocalization()
				->getMaster();
			
		$pageId = $page->getId();

		$auditEm->getUnitOfWork()->clear();
		
		$page = $auditEm->getRepository(AbstractPage::CN())
				->findOneBy(array('id' => $pageId, 'revision' => $this->revision));

		$draftPage = $draftEm->merge($page);

		$draftEm->getRepository(AbstractPage::CN())
				->getNestedSetRepository()
				->add($draftPage);

		$auditEm->getUnitOfWork()->clear();

		$pageLocalizations = $auditEm->getRepository(Localization::CN())
				->findBy(array('master' => $pageId, 'revision' => $this->revision));

		foreach($pageLocalizations as $localization) {

			$draftEm->merge($localization);
			$this->setPageLocalization($localization);

			$placeHolders = $localization->getPlaceHolders();
			foreach($placeHolders as $placeHolder) {
				$draftEm->merge($placeHolder);
			}
			$draftEm->flush();
			
			$localizationId = $localization->getId();

			// page blocks from audit
			$blockEntity = PageRequest::BLOCK_ENTITY;
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
			$propertyEntity = PageRequest::BLOCK_PROPERTY_ENTITY;
			$dql = "SELECT bp FROM $propertyEntity bp 
					WHERE bp.localization = ?0 and bp.revision = ?1";
			$properties = $auditEm->createQuery($dql)
				->setParameters(array($localizationId, $this->revision))
				->getResult();
			
			foreach ($properties as $property) {
				$draftEm->merge($property);
			}
		}

		if ($page instanceof Entity\Template && $page->isRoot()) {

			$templateLayouts = $page->getTemplateLayouts();
			foreach ($templateLayouts as $templateLayout) {
				//$draftEm->merge($templateLayout);
				//$trashEm->remove($templateLayout);
			}
		}

		// TODO: remove audit records also
		$revisionData = $draftEm->find(PageRevisionData::CN(), $this->revision);
		$draftEm->remove($revisionData);
		$draftEm->flush();

		$draftEventManager->dispatchEvent(AuditEvents::pagePostRestoreEvent);
		
		$auditEm->getUnitOfWork()->clear();
		
		return $draftPage;
	}
	
	private function getBlocksInPage(EntityManager $em)
	{
		$localizationId = $this->getPageLocalization()->getId();
		$blockEntity = PageRequest::BLOCK_ENTITY;
		
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
		$propertyEntity = PageRequest::BLOCK_PROPERTY_ENTITY;
		
		$dql = "SELECT bp FROM $propertyEntity bp 
				WHERE bp.localization = ?0";
		
		$properties = $em->createQuery($dql)
				->setParameters(array($localizationId))
				->getResult();
		
		return $properties;
	}
		
}
