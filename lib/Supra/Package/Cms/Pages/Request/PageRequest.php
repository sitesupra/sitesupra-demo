<?php

namespace Supra\Package\Cms\Pages\Request;

use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\Request;
use Supra\Package\Cms\Entity\Abstraction\Entity;
use Supra\Package\Cms\Entity\Template;
use Supra\Package\Cms\Entity\TemplateLayout;
use Supra\Package\Cms\Entity\TemplateLocalization;
use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\Abstraction\PlaceHolder;
use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\PageBlock;
use Supra\Package\Cms\Entity\TemplateBlock;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Pages\Exception\RuntimeException;
use Supra\Package\Cms\Pages\Layout\Theme\ThemeInterface;
use Supra\Package\Cms\Pages\Layout\Theme\ThemeLayoutInterface;
use Supra\Package\Cms\Pages\Set;

/**
 * PageController request class.
 */
abstract class PageRequest extends Request implements ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var Localization
	 */
	private $localization;

	/**
	 * Whether to allow flusing internally
	 * @var boolean
	 */
	protected $allowFlushing = false;

	/**
	 * @var string
	 */
	private $media = TemplateLayout::MEDIA_SCREEN;



	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var Set\PageSet
	 */
	private $pageSet;

	/**
	 * @var Entity\Theme\ThemeLayout
	 */
	private $layout;

	/**
	 * @var Set\PlaceHolderSet
	 */
	protected $placeHolderSet;

	/**
	 * @var Set\BlockSet
	 */
	protected $blockSet;

	/**
	 * @var BlockPropertySet
	 */
	protected $blockPropertySet;

	/**
	 * Block ID array to skip property loading for them.
	 * These are usually blocks with cached results.
	 * @var array
	 */
	private $skipBlockPropertyLoading = array();

	/**
	 * @TODO: remove dependency from Request object?
	 *
	 * @param Request $request source request
	 * @param string $media
	 */
	public function __construct(Request $request, $media = TemplateLayout::MEDIA_SCREEN)
	{
		parent::__construct(
				$request->query->all(),
				$request->request->all(),
				$request->attributes->all(),
				$request->cookies->all(),
				$request->files->all(),
				$request->server->all(),
				$request->content
		);

		$this->setDefaultLocale($request->getLocale());

		$this->media = $media;
	}
	
	/**
	 * Will return true if the resource data should be acquired from the local
	 * object not from the database. Used by history actions.
	 * @param Entity\Abstraction\Entity $entity
	 */
	protected function isLocalResource(Entity $entity)
	{
		return false;
	}

	/**
	 * Appends query result cache information in case of VIEW mode
	 * @param Query $query
	 */
	protected function prepareQueryResultCache(Query $query)
	{
		// Does nothing by default
	}

	/**
	 * @return Localization
	 */
	public function getLocalization()
	{
		return $this->localization;
	}

	/**
	 * @param Localization $localization
	 */
	public function setLocalization(Localization $localization)
	{
		$this->clear();
		$this->localization = $localization;
	}

	public function clear()
	{
		$this->localization = $this->blockPropertySet
				= $this->blockSet
				= $this->layout
				= $this->pageSet
				= $this->placeHolderSet
				= null;
	}

	/**
	 * @param User $user 
	 */
	public function setUser($user)
	{
		$this->user = $user;
	}

	/**
	 * @return User
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * @return string
	 */
	public function getMedia()
	{
		return $this->media;
	}

	/**
	 * @param string $media
	 */
	public function setMedia($media)
	{
		$this->media = $media;
	}

	/**
	 * Helper method to get requested page entity
	 * @return Entity\Abstraction\AbstractPage
	 */
	public function getPage()
	{
		$page = $this->getLocalization()
				->getMaster();

		if ($page === null) {
			throw new RuntimeException(
				"Missing page object for localization [{$this->getLocalization()->getId()}]."
			);
		}

		return $page;
	}

	/**
	 * @return Set\PageSet
	 */
	public function getPageSet()
	{
		if (isset($this->pageSet)) {
			return $this->pageSet;
		}
		
		$localization = $this->getLocalization();
		
		if ($localization instanceof TemplateLocalization) {
			$this->pageSet = $this->getTemplateTemplateHierarchy(
					$localization->getMaster()
			);
			
		} elseif ($localization instanceof PageLocalization) {
			$this->pageSet = $this->getPageTemplateHierarchy($localization);
			
		} else {
			throw new RuntimeException(sprintf(
					'Don\'t know how to get page set for instance of [%s].',
					get_class($localization)
			));
		}

		return $this->pageSet;
	}
	
	/**
	 * @param PageLocalization $localization
	 * @return Set\PageSet
	 */
	protected function getPageTemplateHierarchy(PageLocalization $localization)
	{
		$template = $localization->getTemplate();
		$page = $localization->getPage();

		if (empty($template)) {
			throw new RuntimeException("No template assigned to the page {$localization->getId()}");
		}

		$pageSet = $this->getTemplateTemplateHierarchy($template);
		$pageSet[] = $page;

		return $pageSet;
	}
	
	/**
	 * @param Entity\Template $template
	 * @return Set\PageSet
	 */
	protected function getTemplateTemplateHierarchy(Template $template)
	{
		/* @var $templates Template[] */
		$templates = $template->getAncestors(0, true);
		$templates = array_reverse($templates);

		$pageSet = new Set\PageSet($templates);
		
		return $pageSet;
	}

	/**
	 * @return array
	 */
	public function getPageSetIds()
	{
		return $this->getPageSet()
						->collectIds();
	}

	/**
	 * @return Entity\Template
	 */
	public function getRootTemplate()
	{
		return $this->getPageSet()
						->getRootTemplate();
	}
	
	/**
	 * @return string
	 */
	public function getBlockRequestId()
	{
		$parameters = $this->query;

		if ( ! $parameters->has('block_id')
				&& $this->isMethod('post')) {
			
			$parameters = $this->request;
		}

		return $parameters->get('block_id');
	}
	
	/**
	 * @return boolean
	 */
	public function isBlockRequest()
	{
		$blockId = $this->getBlockRequestId();
		$isBlockRequest = ! empty($blockId);
		
		return $isBlockRequest;
	}

	/**
	 * @return ThemeLayoutInterface
	 */
	public function getLayout()
	{
		if (isset($this->layout)) {
			return $this->layout;
		}

		$layoutName = $this->getPageSet()
				->getLayoutName($this->media);

		return $this->layout = $this->getTheme()
				->getLayout($layoutName);
	}

	/**
	 * @return ThemeInterface
	 */
	public function getTheme()
	{
		return $this->container['cms.pages.theme.provider']
				->getActiveTheme();
	}

	/**
	 * TODO: maybe should return null for history request?
	 * @return array
	 */
	public function getLayoutPlaceHolderNames()
	{
		$layout = $this->getLayout();

		if ($layout === null) {
			return null;
		}

		return $this->getLayoutProcessor()
				->getPlaces($layout->getFileName());
	}
	
	public function getLayoutPlaceHolders()
	{
		$layout = $this->getLayout();
		
		if (is_null($layout)) {
			return null;
		}
		
		return $layout->getPlaceholders();
	}

	/**
	 * @return Set\PlaceHolderSet
	 */
	public function getPlaceHolderSet()
	{
		if (isset($this->placeHolderSet)) {
			return $this->placeHolderSet;
		}

		$localization = $this->getLocalization();
		$localeId = $localization->getLocale();
		$this->placeHolderSet = new Set\PlaceHolderSet($localization);
		
		$pageSetIds = $this->getPageSetIds();
		$layoutPlaceHolderNames = $this->getLayoutPlaceHolderNames();
		
//		$layoutPlaceHolders = $this->getLayoutPlaceHolders();
		
		$entityManager = $this->getEntityManager();
		
		// Skip only if empty array is received
		if (is_array($layoutPlaceHolderNames) && empty($layoutPlaceHolderNames)) {
//		if (is_array($layoutPlaceHolders) && empty($layoutPlaceHolders)) {
			return $this->placeHolderSet;
		}
		
		$currentPageIsLocalResource = false;
		
		if ($this->isLocalResource($localization)) {
			// Ignoring the current page when selecting placeholders by DQL, will acquire from the object
			array_pop($pageSetIds);
			$currentPageIsLocalResource = true;
		}

		// Nothing to search for
		if ( ! empty($pageSetIds)) {

			// Find template place holders
			$qb = $entityManager->createQueryBuilder();

			$qb->select('ph')
					->from(PlaceHolder::CN(), 'ph')
					->join('ph.localization', 'pl')
					->join('pl.master', 'p')
					->andWhere($qb->expr()->in('p.id', $pageSetIds))
					->andWhere('pl.locale = ?0')
					->setParameter(0, $localeId)
					// templates first (type: 0-templates, 1-pages)
					->orderBy('ph.type', 'ASC')
					->addOrderBy('p.level', 'ASC');
			
			if ( ! empty($layoutPlaceHolderNames)) {
				$qb->andWhere($qb->expr()->in('ph.name', $layoutPlaceHolderNames));
			}

			$query = $qb->getQuery();
			$this->prepareQueryResultCache($query);
			$placeHolderArray = $query->getResult();

			foreach ($placeHolderArray as $placeHolder) {
				/* @var $place PlaceHolder */
				$this->placeHolderSet->append($placeHolder);
			}
		}
		
		// Merge the local resource localization into the placeholder set
		if ($currentPageIsLocalResource) {
			$placeHolders = $localization->getPlaceHolders();

			foreach ($placeHolders as $placeHolder) {
				/* @var $place PlaceHolder */
				$this->placeHolderSet->append($placeHolder);
			}
		}

		// @FIXME
//		// Create missing place holders automatically
//		if ( ! $this instanceof HistoryPageRequestEdit) {
//			$this->createMissingPlaceHolders();
//		}

		$this->debug('Count of place holders found: ' . count($this->placeHolderSet));

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

		$entityManager = $this->getEntityManager();

		$this->blockSet = new Set\BlockSet();

		$localFinalPlaceHolders = array();
		
		$finalPlaceHolderIds = array();
		
		$placeHolderSet = $this->getPlaceHolderSet();

//		$allFinalPlaceHolderIds = $placeHolderSet->getFinalPlaceHolders()
//				->collectIds();

		// Filter out the locally managed placeholders (history)
		foreach ($placeHolderSet->getFinalPlaceHolders() as $placeHolder) {
			if ($this->isLocalResource($placeHolder)) {
				$localFinalPlaceHolders[] = $placeHolder;
			} else {
				$finalPlaceHolderIds[] = $placeHolder->getId();
			}
		}
		
		$parentPlaceHolderIds = $placeHolderSet->getParentPlaceHolders()
				->collectIds();

		$blocks = array();
		
		// Just return empty array if no final/parent place holders have been found
		if ( ! empty($finalPlaceHolderIds) || ! empty($parentPlaceHolderIds)) {
			
			// Here we find all 1) locked blocks from templates; 2) all blocks from final place holders
			$qb = $entityManager->createQueryBuilder();
			$qb->select('b')
					->from(Block::CN(), 'b')
					->join('b.placeHolder', 'ph')
					->andWhere('b.inactive = FALSE')
					->orderBy('b.position', 'ASC');

			$expr = $qb->expr();
			$or = $expr->orX();

			// final placeholder blocks
			if ( ! empty($finalPlaceHolderIds)) {
				$or->add($expr->in('ph.id', $finalPlaceHolderIds));
			}

			// locked block condition
			if ( ! empty($parentPlaceHolderIds)) {
				$lockedBlocksCondition = $expr->andX(
						$expr->in('ph.id', $parentPlaceHolderIds), 'b.locked = TRUE'
				);
				$or->add($lockedBlocksCondition);
			}

			$qb->where($or);

			// When specific ID is passed, limit by it
			$blockId = $this->getBlockRequestId();
			if ( ! is_null($blockId)) {
				$qb->andWhere('b.id = :blockId OR b.componentClass = :blockId')
						->setParameter('blockId', $blockId);
			}
			
			// Execute block query
			$query = $qb->getQuery();
			$this->prepareQueryResultCache($query);
			$blocks = $query->getResult();
		}
		
		// Add blocks from locally managed placeholders
		foreach ($localFinalPlaceHolders as $placeHolder) {
			/* @var $placeHolder Entity\Abstraction\PlaceHolder */
			$additionalBlocks = $placeHolder->getBlocks()->getValues();
			$blocks = array_merge($blocks, $additionalBlocks);
		}
		
		$this->debug("Block count found: " . count($blocks));

		// Skip temporary blocks for VIEW mode
		foreach ($blocks as $blockKey => $block) {
			if ($block instanceof TemplateBlock) {
				if ($block->getTemporary()) {
					unset($blocks[$blockKey]);
				}
			}
		}
		
		foreach ($blocks as $blockKey => $block) {
			if ($block instanceof PageBlock) {
				if ($block->isInactive()) {
					unset($blocks[$blockKey]);
				}
			}
		}

		/*
		 * Collect locked blocks first, these are positioned as first blocks in
		 * the placeholder if placeholder is not locked.
		 * First locked blocs are taken from the top template.
		 */
		foreach ($placeHolderSet as $placeHolder) {
			foreach ($blocks as $key => $block) {
			/* @var $block Entity\Abstraction\Block */

				if ($block->getLocked() && $block->getPlaceHolder()->equals($placeHolder)) {
					if ( ! $placeHolder->getLocked()) {
						$this->blockSet[] = $block;
						unset($blocks[$key]);
					}
				}
			}
		}

		// Collect all unlocked blocks
		/* @var $block Entity\Abstraction\Block */
		foreach ($blocks as $block) {
			$this->blockSet[] = $block;
		}
		
		// Ordering the blocks by position in the layout
		$placeHolderNames = $this->getLayoutPlaceHolderNames();
		$this->blockSet->orderByPlaceHolderNameArray($placeHolderNames);

		return $this->blockSet;
	}

	/**
	 * Mark that properties must not be loaded for this block
	 * @param string $blockId
	 */
	public function skipBlockPropertyLoading($blockId)
	{
		$this->skipBlockPropertyLoading[] = $blockId;
	}

	/**
	 * @return Set\BlockPropertySet
	 */
	public function getBlockPropertySet()
	{
		if (isset($this->blockPropertySet)) {
			return $this->blockPropertySet;
		}

		$this->blockPropertySet = new Set\BlockPropertySet();

		$entityManager = $this->getEntityManager();
		$qb = $entityManager->createQueryBuilder();
		$expr = $qb->expr();
		$or = $expr->orX();

		$cnt = 0;

		$blockSet = $this->getBlockSet();
		
		//$sharedPropertyFinder = new SharedPropertyFinder($entityManager);
		
		$localResourceLocalizations = array();

		// Loop generates condition for property getter
		foreach ($blockSet as $block) {
			/* @var $block Entity\Abstraction\Block */

			$blockId = $block->getId();

			// Skip if the block response is read from the cache already
			if (in_array($blockId, $this->skipBlockPropertyLoading)) {
				continue;
			}

			$data = null;

			if ($block->getLocked()) {
				$data = $block->getPlaceHolder()
						->getMaster();
			} else {
				$data = $this->getLocalization();
			}
			
			$dataId = $data->getId();
			
			if ( ! $this->isLocalResource($data)) {

				$and = $expr->andX();
				$and->add($expr->eq('bp.block', '?' . ( ++ $cnt)));
				$qb->setParameter($cnt, $blockId);
				$and->add($expr->eq('bp.localization', '?' . ( ++ $cnt)));
				$qb->setParameter($cnt, $dataId);

				$or->add($and);
			} else {
				// In reality there can be only one local resource localization
				$localResourceLocalizations[$dataId] = $data;
			}

			//$sharedPropertyFinder->addBlock($block, $data);
		}
		
		$result = array();
		
		// Load only if any condition is added to the query
		if ($cnt != 0) {
			$qb->select('bp')
					->from(BlockProperty::CN(), 'bp')
					->where($or);
			$query = $qb->getQuery();

			$this->debug("Running query to find block properties");

			$this->prepareQueryResultCache($query);
			$result = $query->getResult();
		}
		
		// Now merge local resource block properties
		foreach ($localResourceLocalizations as $localization) {
			/* @var $localization Entity\Abstraction\Localization */
			$localProperties = $localization->getBlockProperties()
					->getValues();
			$result = array_merge($result, $localProperties);
		}
		
		$this->blockPropertySet->exchangeArray($result);
		
		// Overwrite some properties with shared data
		//$sharedPropertyFinder->replaceInPropertySet($this->blockPropertySet);

		// Preload blockPropertyMetadata using single query for public requests to increase performance
// @FIXME: check, refactor
//		if ($this instanceof PageRequestView) {
//			$this->preLoadPropertyMetadata();
//		}

		return $this->blockPropertySet;
	}

	/**
	 * @FIXME: refactor this
	 *
	 * Technically, this should optimize blockPropertyMetadata collections loading
	 * by doing it in single query
	 */
	protected function preLoadPropertyMetadata()
	{
		$em = $this->getDoctrineEntityManager();
		$blockPropertyIds = $this->blockPropertySet->collectIds();

		if ( ! empty($blockPropertyIds)) {
			// 3 stages to preload block property metadata
			// stage 1: collect referenced elements IDs
			$metadataEntity = Entity\BlockPropertyMetadata::CN();
			$qb = $em->createQueryBuilder();
			$qb->from($metadataEntity, 'm')
					->join('m.referencedElement', 'el')
					->select('m, el')
					->where($qb->expr()->in('m.blockProperty', $blockPropertyIds));

			$query = $qb->getQuery();
			$this->prepareQueryResultCache($query);
			$metadataArray = $query->getResult();

			// stage 2: load referenced elements with DQL, so they will be stored in doctrine cache
			$referencedElements = array();
			foreach ($metadataArray as $metadata) {
				/* @var $metadata Entity\BlockPropertyMetadata */
				$referencedElement = $metadata->getReferencedElement();
				$referencedElementId = $referencedElement->getId();
				$referencedElements[$referencedElementId] = $referencedElement;
			}

			$elementPageIds = array();
			foreach ($referencedElements as $element) {
				if ($element instanceof Entity\ReferencedElement\LinkReferencedElement) {
					$pageId = $element->getPageId();
					if ( ! empty($pageId)) {
						$elementPageIds[] = $element->getPageId();
					}
				}
			}

			if ( ! empty($elementPageIds)) {
				$qb = $em->createQueryBuilder();
				$qb->from(Entity\PageLocalization::CN(), 'l')
						->join('l.master', 'm')
						->join('l.path', 'p')
						->select('l, m, p')
						->where($qb->expr()->in('l.master', $elementPageIds))
						->andWhere('l.locale = :locale')
						->setParameter('locale', $this->getLocale());

				$query = $qb->getQuery();
				$this->prepareQueryResultCache($query);
				$localizations = $query->getResult();
				
				if (empty($localizations)) {
					$localizations = array();
				}

				$localizationIds = array();

				foreach ($localizations as $pageLocalization) {
					$entityData = $em->getUnitOfWork()
							->getOriginalEntityData($pageLocalization);

					$localizationIds[] = $entityData['master_id'];
				}

				if ( ! empty($localizationIds) && ! empty($localizations)) {
					$localizations = array_combine($localizationIds, $localizations);
				}

				foreach ($referencedElements as $element) {
					if ($element instanceof Entity\ReferencedElement\LinkReferencedElement) {
						$pageId = $element->getPageId();
						if (isset($localizations[$pageId])) {
							$element->setPageLocalization($localizations[$pageId]);
						}
					}
				}
			}

			// stage 3: load metadata
			foreach ($this->blockPropertySet as $blockProperty) {
				/* @var $blockProperty BlockProperty */
				$blockProperty->initializeOverridenMetadata();
			}

			foreach ($metadataArray as $propertyMetadata) {
				/* @var $propertyMetadata BlockPropertyMetadata */
				$property = $propertyMetadata->getBlockProperty();
				$propertyId = $property->getId();

				$propertyData = $em->getUnitOfWork()
						->getOriginalEntityData($propertyMetadata);

				if (isset($propertyData['referencedElement_id'])) {

					$elementId = $propertyData['referencedElement_id'];
					if (isset($referencedElements[$elementId])) {
						$propertyMetadata->setOverridenReferencedElement($referencedElements[$elementId]);
					}
				}
				
				// Can't add for $property because of shared property feature
				$this->blockPropertySet->addOverridenMetadata($propertyId, $propertyMetadata);
			}
		}
	}

	/**
	 * 
	 */
	public function createMissingPlaceHolders($forceUseTemplateBlocks = false)
	{
		$layoutPlaceHolders = $this->getLayoutPlaceHolders();

		if ($layoutPlaceHolders->isEmpty()) {
			return;
		}

//		$this->createMissingPlaceHolderGroups();
		
		$placeHolderSet = $this->getPlaceHolderSet();

		$entityManager = $this->getDoctrineEntityManager();
		$localization = $this->getLocalization();

		$finalPlaceHolders = $placeHolderSet->getFinalPlaceHolders();
		$parentPlaceHolders = $placeHolderSet->getParentPlaceHolders();
		
		$localizationGroups = $localization->getPlaceHolderGroups();

		foreach ($layoutPlaceHolders as $layoutPlaceHolder) {
			
			$placeHolder = null;
			$parentPlaceHolder = null;
			
			$name = $layoutPlaceHolder->getName();
			
			if ( ! $finalPlaceHolders->offsetExists($name)) {

				// Check if page doesn't have it already set locally
				//$placeHolder = null;
				$knownPlaceHolders = $localization->getPlaceHolders();

				if ($knownPlaceHolders->offsetExists($name)) {
					$placeHolder = $knownPlaceHolders->offsetGet($name);
				}
				
				if (empty($placeHolder)) {
					// Copy unlocked blocks from the parent template
					$parentPlaceHolder = $parentPlaceHolders->getLastByName($name);
					
					// TODO: should move to recursive clone
					$placeHolder = Entity\Abstraction\PlaceHolder::factory($localization, $name, $parentPlaceHolder);
					$placeHolder->setMaster($localization);
				}
			
				// Persist only for draft connection with ID generation
				if ($this instanceof PageRequestEdit) {
					$entityManager->persist($placeHolder);
				}

				$placeHolderSet->append($placeHolder);
			}
			
			if ($placeHolder === null) {
				$placeHolder = $finalPlaceHolders->offsetGet($name);
				
				if ( ! $placeHolder->getLocalization()->equals($localization)) {
					continue;
				}
			} 			
			
			if ($placeHolder->getGroup() === null) {
				
				$sourceGroup = null;
				if ($parentPlaceHolder === null) {
					$sourceGroup = $layoutPlaceHolder->getGroup();
				} else {
					$sourceGroup = $parentPlaceHolder->getGroup();
				}
				
				if ($sourceGroup !== null) {
						
					$sourceGroupName = $sourceGroup->getName();	
					
					if ($localizationGroups->offsetExists($sourceGroupName)) {
						$localizationGroup = $localizationGroups->get($sourceGroupName);
					} else {
						$localizationGroup = Entity\PlaceHolderGroup::factory($sourceGroup);
						$localization->addPlaceHolderGroup($localizationGroup);
								
						if ($this instanceof PageRequestEdit) {
							$entityManager->persist($localizationGroup);
						}
					}
					
					$localizationGroup->addPlaceholder($placeHolder);
					$placeHolder->setGroup($localizationGroup);
				}	
			}	
		}
		
		if ($this instanceof PageRequestEdit && $forceUseTemplateBlocks) {
			$this->copyBlocksFromTemplate();
		}

		// Flush only for draft connection with ID generation
		if ($this instanceof PageRequestEdit && $this->allowFlushing) {
			$entityManager->flush();
		}
	}
	
//	/**
//	 * 
//	 */
//	protected function createMissingPlaceHolderGroups()
//	{
//		$em = $this->getDoctrineEntityManager();
//		
//		$localization = $this->getLocalization();
//		
//		$currentGroups = $localization->getPlaceHolderGroups();
//		$currentGroupKeys = $currentGroups->getKeys();
//		
//		if ($localization instanceof Entity\TemplateLocalization) {
//
//			$layout = $this->getLayout();
//			$layoutGroups = $layout->getPlaceHolderGroups();
//			
//			foreach ($layoutGroups as $layoutGroup) {
//				/* @var $layoutGroup Entity\Theme\ThemeLayoutPlaceholderGroup */
//				$groupName = $layoutGroup->getName();
//
//				if ( ! in_array($groupName, $currentGroupKeys)) {
//					
//					$templateGroup = Entity\PlaceHolderGroup::factory($layoutGroup);
//					$localization->addPlaceHolderGroup($templateGroup);
//				
//					if ($this instanceof PageRequestEdit) {
//						$em->persist($templateGroup);
//					}
//				}
//			}
//		}
//		else if ($localization instanceof Entity\PageLocalization) {
//
//			$templateLocalization = $localization->getTemplate()
//					->getLocalization($localization->getLocale());
//			
//			$groupsInTemplate = $templateLocalization->getPlaceHolderGroups();
//			
//			$layout = $this->getLayout();
//			$layoutGroups = $layout->getPlaceHolderGroups();			
//			
//			foreach ($layoutGroups as $layoutGroup) {
//				$groupName = $layoutGroup->getName();
//				
//				if ( ! in_array($groupName, $currentGroupKeys)) {
//					
//					$sourceGroup = $layoutGroup;
//					if ($groupsInTemplate->offsetExists($groupName)) {
//						$sourceGroup = $groupsInTemplate->get($groupName);
//						
//						//
//						if ($sourceGroup->getLocked()) {
//							continue;
//						}
//					}
//					
//					$newGroup = Entity\PlaceHolderGroup::factory($sourceGroup);
//					
//					$localization->addPlaceHolderGroup($newGroup);
//					
//					if ($this instanceof PageRequestEdit) {
//						$em->persist($newGroup);
//					}
//					
//				}
//			}
//		}
//	}

	/**
	 * 
	 */
	public function createMissingBlockProperties()
	{
		$entityManager = $this->getDoctrineEntityManager();
		$blocks = $this->getBlockSet();

		$pageSet = $this->getPageSet();
		$length = $pageSet->count();

		if ($length <= 1) {
			return;
		}

		$template = $pageSet->offsetGet($length - 2);

		/* @var $template Entity\Template */
		
		if (empty($template)) {
			return;
		}

		$localization = $this->getLocalization();

		foreach ($blocks as $block) {
			/* @var $block \Supra\Controller\Pages\Entity\Abstraction\Block */

			if ($block->getLocked()) {
				continue;
			}
			
			$placeHolder = $block->getPlaceHolder();
			/* @var $placeHolder \Supra\Controller\Pages\Entity\Abstraction\PlaceHolder */

			if ( ! $placeHolder->getLocked()) {
				continue;
			}

			$templateId = $template->getId();
			$blockId = $block->getId();
			$localeId = $this->getLocale();

			//TODO: Move after loop
			$blockPropertiesToCopy = $entityManager->createQueryBuilder()
					->select('bp')
					->from(BlockProperty::CN(), 'bp')
					->join('bp.localization', 'l')
					->andWhere('l.locale = :locale')
					->andWhere('l.master = :template')
					->andWhere('bp.block = :block')
					->setParameter('template', $templateId)
					->setParameter('block', $blockId)
					->setParameter('locale', $localeId)
					->getQuery()
					->getResult();

			foreach ($blockPropertiesToCopy as $blockProperty) {
				/* @var $blockProperty BlockProperty */

				$metadataCollection = $blockProperty->getMetadata();

				$blockProperty = clone($blockProperty);
				$blockProperty->resetLocalization();
				$blockProperty->setLocalization($localization);

				$entityManager->persist($blockProperty);

				foreach ($metadataCollection as $metadata) {
					/* @var $metadata \Supra\Controller\Pages\Entity\BlockPropertyMetadata */
					$metadata = clone($metadata);
					$metadata->setBlockProperty($blockProperty);
					$entityManager->persist($metadata);
				}
			}
		}

		// Flush only for draft connection with ID generation
		if ($this instanceof PageRequestEdit && $this->allowFlushing) {
			$entityManager->flush();
		}
	}

	/**
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	protected function debug($message)
	{
		$this->container['debug_bar.debug_bar']['messages']->info($message);
	}

	/**
	 * @TODO: generate PlaceHolders and response part objects on demand,
	 *		instead of processing layout file twice.
	 *
	 * @return ProcessorInterface
	 */
	protected function getLayoutProcessor()
	{
		return $this->container['cms.pages.layout_processor'];
	}

	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	abstract protected function getEntityManager();
}
