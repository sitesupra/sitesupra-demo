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

/**
 * Request object for history mode requests
 */
class HistoryPageRequestEdit extends PageRequest
{
	/**
	 * Contains revision id string
	 * @var string
	 */
	protected $revision;
	protected $revisionArray;
	protected $revisionIds;
	protected $hasRemoveRevision = false;
	protected $removeRevisionIds = array();
	
	/**
	 * @var array
	 */
	protected $pageLocalizations;
	
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
		
		$revisionIds = \Supra\Database\Entity::collectIds($revisions);
		$this->revisionIds = $revisionIds;
		
		// workaround for removed entities
		foreach($revisions as $key => $revision) {
			if ($revision->getType() == PageRevisionData::TYPE_REMOVED) {
				$this->hasRemoveRevision = true;
				$this->removeRevisionIds[] = $revision->getId();
			}
			
//			if ($revision->getType() == PageRevisionData::TYPE_HISTORY_RESTORE) {
//				unset($this->revisionIds[$key]);
//				unset($this->revisionArray[$key]);
//			}
		}
		
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

		$localization = $this->getPageLocalization();
		
		$this->placeHolderSet = new Set\PlaceHolderSet($localization);
		
		$pageSetIds = $this->getPageSetIds();
		$layoutPlaceHolderNames = $this->getLayoutPlaceHolderNames();
		
		if (empty($pageSetIds) || empty($layoutPlaceHolderNames)) {
			return $this->placeHolderSet;
		}
		
		$localeId = $localization->getLocale();
		
		// Draft connection
		$entityManager = ObjectRepository::getEntityManager(PageController::SCHEMA_DRAFT);
		
		$params = array(
			'placeholderNames' => $layoutPlaceHolderNames,
			'pageSetIds' => $pageSetIds,
			'locale' => $localeId,
		);
		
		$qb = $entityManager->createQueryBuilder();
		$qb->select('ph')
				->from(Entity\Abstraction\PlaceHolder::CN(), 'ph')
				->join('ph.localization', 'pl')
				->join('pl.master', 'p')
				->where('ph.type = 0 AND ph.name in (:placeholderNames)')
				->andWhere('p.id in (:pageSetIds)')
				->andWhere('pl.locale = :locale')
				->addOrderBy('p.level', 'ASC')
				->setParameters($params)
				;
		
		$templatePlaceHolders = $qb->getQuery()
				->getResult();
		
		$pagePlaceholders = $localization->getPlaceHolders()
				->getValues();

		$placeHolders = array_merge($templatePlaceHolders, $pagePlaceholders);
		unset($templatePlaceHolders, $pagePlaceholders);

		foreach($placeHolders as $placeHolder) {
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
		
		$this->blockSet = new Set\BlockSet();
		
		$auditEntityManager = $this->getDoctrineEntityManager();
		
		$placeHolderSet = $this->getPlaceHolderSet();

		$finalPlaceHolderIds = $placeHolderSet->getFinalPlaceHolders()
				->collectIds();

		$parentPlaceHolderIds = $placeHolderSet->getParentPlaceHolders()
				->collectIds();

		if (empty($finalPlaceHolderIds) && empty($parentPlaceHolderIds)) {
			return $this->blockSet;
		}
		
		$params = array(
			'placeHolders' => $finalPlaceHolderIds,
			'revision' => $this->revision,
		);
		
		$qb = $auditEntityManager->createQueryBuilder();
		$qb->select('b')
				->from(Entity\Abstraction\Block::CN(), 'b')
				->join('b.placeHolder', 'ph')
				->where('ph.id in (:placeHolders)')
				->andWhere('b.revision = :revision')
				->orderBy('b.position', 'ASC')
				->setParameters($params)
				;

		$auditBlocks = $qb->getQuery()
				->getResult();
		
		$draftEntityManager = ObjectRepository::getEntityManager('Supra\Cms');
		$qb = $draftEntityManager->createQueryBuilder();
		$qb->select('b')
				->from(Entity\Abstraction\Block::CN(), 'b')
				->join('b.placeHolder', 'ph')
				->orderBy('b.position', 'ASC')
				;
		
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
		$qb->where($and)
				->andWhere('ph.type = 0');
		
		$draftBlocks = $qb->getQuery()
				->getResult();
		
		$missingBlocks = array_diff($draftBlocks, $auditBlocks);
		if ( ! empty ($missingBlocks)) {
			
			$page = $this->getPage();
			
			foreach($missingBlocks as $key => $block) {
				/* @var $block Entity\Abstraction\Block */
				
				$master = $block->getPlaceHolder()
						->getMaster()
						->getMaster();
				
				if ($page->equals($master)) {
					unset($missingBlocks[$key]);
				}
			}
			
			$auditBlocks = array_merge($missingBlocks, $auditBlocks);
		}
		
		$result = array();
		foreach ($auditBlocks as $block) {
			if ($block->inPlaceHolder($parentPlaceHolderIds)) {
				$result[] = $block;
			}
			else if ($block->inPlaceHolder($finalPlaceHolderIds)) {
				$result[] = $block;
			}
		}
		
		//FIXME!: speed up
		$em = $this->getDoctrineEntityManager();
		if ( ! empty($this->revisionArray)) {
			$em->getUnitOfWork()->clear();
			$qb = $em->createQueryBuilder();

			$qb->select('b.id, b.revision')
					->from(Entity\Abstraction\Block::CN(), 'b')
					->join('b.placeHolder', 'ph')
					->where('b.revision in (:revisions)')
					->andWhere('ph.id in (:placeHolders)')
					->orderBy('b.revision', 'DESC')
					->setParameters(array('revisions' => $this->revisionIds, 'placeHolders' => $finalPlaceHolderIds))
					;

			$blocks = $qb->getQuery()
					->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

			if ( ! empty($blocks)) {

				$qb = $em->createQueryBuilder();
				$expr = $qb->expr();
				$or = $expr->orX();

				$blockIds = array();
				$count = 0;
				foreach($blocks as $block) {
					if ( ! in_array($block['id'], $blockIds)) {
						$and = $expr->andX();
						$and->add($expr->eq('b.id', '?' . (++$count)));
						$qb->setParameter($count, $block['id']);
						$and->add($expr->eq('b.revision', '?' . (++$count)));
						$qb->setParameter($count, $block['revision']);

						$or->add($and);

						$blockIds[] = $block['id'];
					}
				}

				$em->getUnitOfWork()->clear();

				$qb->select('b')
						->from(Entity\Abstraction\Block::CN(), 'b')
						->where($or);
				$blocks = $qb->getQuery()
						->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_OBJECT);
				$exchangeableBlockIds = \Supra\Database\Entity::collectIds($blocks);

				foreach($result as $key => $block) {

					$id = $block->getId();
					if (in_array($id, $exchangeableBlockIds)) {
						$offset = array_search($id, $exchangeableBlockIds);
						$result[$key] = $blocks[$offset];

					}
				}
				
				$resultIds = \Supra\Database\Entity::collectIds($result);
				$blockIds = \Supra\Database\Entity::collectIds($blocks);
				
				$result = array_combine($resultIds, $result);
				$blocks = array_combine($blockIds, $blocks);
				
				$result = array_merge($result, $blocks);
			}

			// FIXME: heavy workaround for block sorting
			$blockSorting = array();
			foreach($result as $block) {
				$placeHolderId = $block->getPlaceHolder()->getId();

				$blockPosition = $block->getPosition();

				$blockSorting[$placeHolderId][$blockPosition] = $block;
			}

			foreach($blockSorting as $key => $placeHolder) {
				ksort($placeHolder);
				$blockSorting[$key] = $placeHolder;
			}

			$result = array();
			foreach($blockSorting as $placeHolder) {
				$result = array_merge($result, array_values($placeHolder));
			}
			
		}
		
		$result = $this->checkRemovedEntities($result);
		
		$this->blockSet->exchangeArray($result);
		
				
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
		
		//FIXME: speed up
		if ( ! empty($this->revisionArray)) {
			$qb = $em->createQueryBuilder();

			$qb->select('bp.id, bp.revision')
					->from(Entity\BlockProperty::CN(), 'bp')
					->where('bp.revision in (:revisions)')
					->andWhere('bp.block in (:blocks)')
					->orderBy('bp.revision', 'DESC')
					->setParameters(array('revisions' => $this->revisionIds, 'blocks' => $blockSetIds))
					;

			$properties = $qb->getQuery()
					->setHint(Query::HINT_INCLUDE_META_COLUMNS, true)
					->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
			
			if ( ! empty($properties)) {

				$qb = $em->createQueryBuilder();
				$expr = $qb->expr();
				$or = $expr->orX();

				$propIds = array();
				$count = 0;
				foreach($properties as $property) {
					if ( ! in_array($property['id'], $propIds)) {
						$and = $expr->andX();
						$and->add($expr->eq('bp.id', '?' . (++$count)));
						$qb->setParameter($count, $property['id']);
						$and->add($expr->eq('bp.revision', '?' . (++$count)));
						$qb->setParameter($count, $property['revision']);

						$or->add($and);

						$propIds[] = $property['id'];

					}
				}

				$em->getUnitOfWork()->clear();

				$qb->select('bp')
						->from(Entity\BlockProperty::CN(), 'bp')
						->where($or);
				$properties = $qb->getQuery()
						->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_OBJECT);
				$exchangeablePropertyIds = \Supra\Database\Entity::collectIds($properties);

				foreach($result as $key => $blockProperty) {

					$id = $blockProperty->getId();
					if (in_array($id, $exchangeablePropertyIds)) {
						$offset = array_search($id, $exchangeablePropertyIds);
						$result[$key] = $properties[$offset];
					}
				}


				$resultIds = \Supra\Database\Entity::collectIds($result);
				$propertyIds = \Supra\Database\Entity::collectIds($properties);

				$result = array_combine($resultIds, $result);
				$properties = array_combine($propertyIds, $properties);

				$result = array_merge($result, $properties);
			}
		}
		
		$result = $this->checkRemovedEntities($result);
		
		$this->blockPropertySet->exchangeArray($result);
		
		return $this->blockPropertySet;
	}
	
	/**
	 * Does the history localization version restoration
	 */
	public function restoreLocalization()
	{
		$draftEntityManager = ObjectRepository::getEntityManager(PageController::SCHEMA_DRAFT);
		
		$draftEntityManager->getEventManager()
				->dispatchEvent(AuditEvents::pagePreRestoreEvent);
		
		$draftEntityManager->getProxyFactory()->getProxy(Entity\ReferencedElement\LinkReferencedElement::CN(), -1);
		$draftEntityManager->getProxyFactory()->getProxy(Entity\PageLocalizationPath::CN(), -1);
		
		$auditLocalization = $this->getPageLocalization();
		
		if ($auditLocalization instanceof Entity\PageLocalization) {
			// FIXME! without this path is loaded from wrong Path entity table (public one)
			$draftLocalization = $draftEntityManager->find(Entity\PageLocalization::CN(), $auditLocalization->getId());
			$draftPath = $draftLocalization->getPathEntity();
			$auditLocalization->setPathEntity($draftPath);
		}
			
		$localization = $draftEntityManager->merge($auditLocalization);
	
		// merge placeholders
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
		
		foreach($blockToRemove as $blockToRemoveId) {
			foreach($existingDraftBlocks as $existingDraftBlock) {
				if ($existingDraftBlock->getId() == $blockToRemoveId) {
					$draftEntityManager->remove($existingDraftBlock);
				}
			}
		}
		
		// merge blocks
		foreach($auditBlocks as $auditBlock) {
			$draftEntityManager->merge($auditBlock);
		}
		
		
		// remove all existing draft metadata
		$qb = $draftEntityManager->createQueryBuilder();
		$qb->select('m')
				->from(Entity\BlockPropertyMetadata::CN(), 'm')
				->join('m.blockProperty', 'bp')
				->where('bp.localization = :localizationId')
				->setParameter('localizationId', $localization->getId())
						;
		
		$existingDraftMetadata = $qb->getQuery()
				->getResult();
		
		foreach($existingDraftMetadata as $existingDraftMetadataItem) {
			$draftEntityManager->remove($existingDraftMetadataItem);
			
			$existingDraftReferencedElement = $existingDraftMetadataItem->getReferencedElement();
			if ( ! is_null($existingDraftReferencedElement)) {
				$draftEntityManager->remove($existingDraftReferencedElement);
			}
		}
		
		$draftEntityManager->flush();
		
//		$draftEntityManager->createQuery('select bpm from ' . Entity\BlockPropertyMetadata::CN() . ' bpm where bpm.blockProperty in (?0)')
//				->setParameters(array($existingBlockPropertyId))
//				->execute();
//		
//		$existingBlockProperties = $draftEm->createQuery('select bp from ' . Entity\BlockProperty::CN() . ' bp where bp.localization = ?0')
//				->setParameters(array($pageLocalization->getId()))
//				->getResult();
//		$existingBlockPropertyId = Entity\Abstraction\Entity::collectIds($existingBlockProperties);
		
		$auditEntityManager = ObjectRepository::getEntityManager('#audit');
		
		// block properties
		$draftProperties = $this->getPageBlockProperties($draftEntityManager);
		$draftPropertyIds = Entity\Abstraction\Entity::collectIds($draftProperties);
					
		$auditProperties = $this->getBlockPropertySet()
				->getPageProperties($auditLocalization);
		$auditPropertyIds = Entity\Abstraction\Entity::collectIds($auditProperties);
		
		$propertyToRemove = array_diff($draftPropertyIds, $auditPropertyIds);
		foreach($propertyToRemove as $propertyToRemoveId) {
			foreach($draftProperties as $draftProperty) {
				if ($draftProperty->getId() == $propertyToRemoveId) {
					$draftEntityManager->remove($draftProperty);
					break;
				}
			}
		}
		$draftEntityManager->flush();
		
		foreach ($auditProperties as $auditProperty) {
			$draftEntityManager->merge($auditProperty);
		}
		
		$auditPropertyIds = $auditProperties->collectIds();
	
		$params = array(
			'propertyIds' => $auditPropertyIds,
			'revision' => $this->revision,
		);
		
		$auditMetadata = array();
		if ( ! empty($auditPropertyIds)) {
			$qb = $auditEntityManager->createQueryBuilder();
			$qb->select('m')
					->from(Entity\BlockPropertyMetadata::CN(), 'm')
					->where('m.blockProperty IN (:propertyIds) AND m.revision = :revision')
					->setParameters($params);

			$auditMetadata = $qb->getQuery()
					->getResult();
		}
		
//		$auditMetadata = $auditEntityManager->createQuery('select bpm from ' . Entity\BlockPropertyMetadata::CN() . ' bpm where bpm.blockProperty = ?0 and bpm.revision = ?1')
//					->setParameters(array($property->getId(), $this->revision))
//					->getResult();
			
		foreach($auditMetadata as $auditMetadataItem) {
			$metadataData = $auditEntityManager->getUnitOfWork()
					->getOriginalEntityData($auditMetadataItem);
			
			if (isset($metadataData['referencedElement_id'])) {
				$referencedElement = $auditEntityManager->createQuery('select re from ' . Entity\ReferencedElement\ReferencedElementAbstract::CN() . 
							' re WHERE re.id = ?0 AND re.revision = ?1')
						->setParameters(array($metadataData['referencedElement_id'], $this->revision))
						->getResult();
				}
				
			if ( ! empty($referencedElement)) {
				$draftEntityManager->merge($referencedElement[0]);
			}
				
			$draftEntityManager->merge($auditMetadataItem);
		}
		
		//if ($page instanceof Entity\Template 
		//		&& $page->isRoot()) {
		//		
		//	$layouts = $page->getTemplateLayouts();
		//	foreach ($layouts as $layout) {
		//		$draftEm->merge($layout);
		//	}
		//}
		
		//$listeners = $draftEm->getEventManager()->getListeners(\Doctrine\ORM\Events::onFlush);
		//foreach ($listeners as $listener) {
		//	if ($listener instanceof \Supra\Controller\Pages\Listener\PagePathGenerator) {
		//		$listeners = $draftEm->getEventManager()->removeEventListener(\Doctrine\ORM\Events::onFlush, $listener);
		//	}
		//}
		
		$draftEntityManager->flush();
		
		$pageEventArgs = new PageEventArgs();
		$pageEventArgs->setProperty('localizationId', $localization->getId());
		$pageEventArgs->setProperty('entityManager', $draftEntityManager);
		
		$draftBlocks = $this->getBlocksInPage($draftEntityManager);
		$draftBlockIdList = Entity\Abstraction\Entity::collectIds($draftBlocks);
		$pageEventArgs->setProperty('blockIdCollection', $draftBlockIdList);
		
		$draftProperties = $this->getBlockPropertySet()
				->getPageProperties($localization);
		$draftPropertyIds = $draftProperties->collectIds();
		$pageEventArgs->setProperty('blockPropertyIdCollection', $draftPropertyIds);
		
		$draftEntityManager->getEventManager()
				->dispatchEvent(AuditEvents::pagePostRestoreEvent, $pageEventArgs);
	
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
			
			if ($localization instanceof Entity\PageLocalization) {
				$localization->initializeProxyAssociations();
			}

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

//		if ($page instanceof Entity\Template && $page->isRoot()) {
//
//			$templateLayouts = $page->getTemplateLayouts();
//			foreach ($templateLayouts as $templateLayout) {
//				//$draftEm->merge($templateLayout);
//				//$trashEm->remove($templateLayout);
//			}
//		}

		$revisionData = $draftEm->find(PageRevisionData::CN(), $this->revision);
		/* @var $revisionData PageRevisionData */
		$revisionData->setType(PageRevisionData::TYPE_RESTORED);
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
	
	private function checkRemovedEntities(array $entities)
	{
		if ($this->hasRemoveRevision) {
			foreach ($entities as $key => $entity) {
				/* @var $entity Entity\Abstraction\Entity */
				$revisionId = $entity->getRevisionId();
				if (in_array($revisionId, $this->removeRevisionIds)) {
					unset($entities[$key]);
				}
			}
		}
		
		return $entities;
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
	
}