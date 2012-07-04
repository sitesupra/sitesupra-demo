<?php

namespace Supra\Controller\Pages\Request;

use Supra\Request\HttpRequest;
use Supra\Controller\Pages\Entity;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\Set;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Log\Writer\WriterAbstraction;
use Supra\Database\Doctrine\Hydrator\ColumnHydrator;
use Supra\Controller\Pages\Entity\BlockProperty;
use Doctrine\ORM\Query;
use Supra\Controller\Pages\Configuration\BlockControllerConfiguration;
use Supra\Controller\Pages\Configuration\BlockPropertyConfiguration;

/**
 * Page controller request
 */
abstract class PageRequest extends HttpRequest
{
	/**
	 * @var WriterAbstraction
	 */
	protected $log;

	/**
	 * Whether to allow flusing internally
	 * @var boolean
	 */
	protected $allowFlushing = false;

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	private $doctrineEntityManager;

	/**
	 * @var string
	 */
	private $locale;

	/**
	 * @var string
	 */
	private $media = Entity\TemplateLayout::MEDIA_SCREEN;

	/**
	 * @var Entity\Abstraction\Localization
	 */
	private $pageData;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var Set\PageSet
	 */
	private $pageSet;

	/**
	 * @var Entity\ThemeLayout
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
	 * @param string $locale
	 * @param string $media
	 */
	public function __construct($locale, $media = Entity\TemplateLayout::MEDIA_SCREEN)
	{
		parent::__construct();

		$this->locale = $locale;
		$this->media = $media;
		$this->log = ObjectRepository::getLogger($this);
	}
	
	/**
	 * Will return true if the resource data should be acquired from the local
	 * object not from the database. Used by history actions.
	 * @param Entity\Abstraction\Entity $entity
	 */
	protected function isLocalResource(Entity\Abstraction\Entity $entity)
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
	 * @return Entity\Abstraction\Localization
	 */
	public function getPageLocalization()
	{
		return $this->pageData;
	}

	/**
	 * @param Entity\Abstraction\Localization $pageData
	 */
	public function setPageLocalization(Entity\Abstraction\Localization $pageData)
	{
		$this->pageData = $pageData;
		
		// Unset cache
		$this->blockPropertySet = null;
		$this->blockSet = null;
		$this->layout = null;
		$this->pageSet = null;
		$this->placeHolderSet = null;
	}

	public function resetPageLocalization()
	{
		$this->pageData = null;
	}

	/**
	 * @param \Doctrine\ORM\EntityManager $em
	 */
	public function setDoctrineEntityManager(\Doctrine\ORM\EntityManager $em)
	{
		$this->doctrineEntityManager = $em;
	}

	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	public function getDoctrineEntityManager()
	{
		return $this->doctrineEntityManager;
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
	 * @param string $locale
	 */
	public function setLocale($locale)
	{
		$this->locale = $locale;
	}

	/**
	 * @return string
	 */
	public function getLocale()
	{
		return $this->locale;
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
		$master = $this->getPageLocalization()
				->getMaster();

		if (is_null($master)) {
			$localizationId = $this->getPageLocalization()
					->getId();

			throw new Exception\RuntimeException("Master page entity is missing for localization [{$localizationId}]");
		}

		return $master;
	}

	/**
	 * @return Set\PageSet
	 */
	public function getPageSet()
	{
		if (isset($this->pageSet)) {
			return $this->pageSet;
		}
		
		$localization = $this->getPageLocalization();
		
		if ($localization instanceof Entity\TemplateLocalization) {
			$template = $localization->getMaster();
			$this->pageSet = $this->getTemplateTemplateHierarchy($template);
		} elseif ($localization instanceof Entity\PageLocalization) {
			$this->pageSet = $this->getPageTemplateHierarchy($localization);
		} else {
			throw new Exception\RuntimeException("Template hierarchy cannot be called for a localization of type " . $localization::CN());
		}

		return $this->pageSet;
	}
	
	/**
	 * @param Entity\PageLocalization $localization
	 * @return Set\PageSet
	 */
	protected function getPageTemplateHierarchy(Entity\PageLocalization $localization)
	{
		$template = $localization->getTemplate();
		$page = $localization->getPage();

		if (empty($template)) {
			throw new Exception\RuntimeException("No template assigned to the page {$localization->getId()}");
		}

		$pageSet = $this->getTemplateTemplateHierarchy($template);
		$pageSet[] = $page;

		return $pageSet;
	}
	
	/**
	 * @param Entity\Template $template
	 * @return Set\PageSet
	 */
	protected function getTemplateTemplateHierarchy(Entity\Template $template)
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
		$blockId = $this->getQueryValue('block_id', null);
		
		return $blockId;
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
	 * @return Entity\ThemeLayout
	 */
	public function getLayout()
	{
		if (isset($this->layout)) {
			return $this->layout;
		}

		$this->layout = $this->getPageSet()
				->getLayout($this->media);

		return $this->layout;
	}

	/**
	 * TODO: maybe should return null for history request?
	 * @return array
	 */
	public function getLayoutPlaceHolderNames()
	{
		$layout = $this->getLayout();
		
		if (is_null($layout)) {
			return null;
		}
		
		return $layout->getPlaceHolderNames();
	}

	/**
	 * @return Set\PlaceHolderSet
	 */
	public function getPlaceHolderSet()
	{
		if (isset($this->placeHolderSet)) {
			return $this->placeHolderSet;
		}

		$localization = $this->getPageLocalization();
		$localeId = $localization->getLocale();
		$this->placeHolderSet = new Set\PlaceHolderSet($localization);
		
		$pageSetIds = $this->getPageSetIds();
		$layoutPlaceHolderNames = $this->getLayoutPlaceHolderNames();
		
		$em = $this->getDoctrineEntityManager();
		
		// Skip only if empty array is received
		if (is_array($layoutPlaceHolderNames) && empty($layoutPlaceHolderNames)) {
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
			$qb = $em->createQueryBuilder();

			$qb->select('ph')
					->from(Entity\Abstraction\PlaceHolder::CN(), 'ph')
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
		
		// Create missing place holders automatically
		$this->createMissingPlaceHolders();

		\Log::debug('Count of place holders found: ' . count($this->placeHolderSet));

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

		$em = $this->getDoctrineEntityManager();
		$this->blockSet = new Set\BlockSet();

		$localFinalPlaceHolders = array();
		
		$finalPlaceHolderIds = array();
		$parentPlaceHolderIds = array();
		
		$placeHolderSet = $this->getPlaceHolderSet();

		$allFinalPlaceHolderIds = $placeHolderSet->getFinalPlaceHolders()
				->collectIds();

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
			$qb = $em->createQueryBuilder();
			$qb->select('b')
					->from(Entity\Abstraction\Block::CN(), 'b')
					->join('b.placeHolder', 'ph')
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
			$blockId = $this->getQueryValue('block_id', null);
			if ( ! is_null($blockId)) {
				$qb->andWhere('b.id = :blockId')
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
		
		\Log::debug("Block count found: " . count($blocks));

		// Skip temporary blocks for VIEW mode
		foreach ($blocks as $blockKey => $block) {
			if ($block instanceof Entity\TemplateBlock) {
				if ($block->getTemporary()) {
					unset($blocks[$blockKey]);
				}
			}
		}

		/*
		 * Collect locked blocks first, these are positioned as first blocks in
		 * the placeholder. First are from the top template.
		 */
		/* @var $block Entity\Abstraction\Block */
		$placeHolderIds = $placeHolderSet->collectIds();
		$placeHolderIds = array_unique($placeHolderIds);
		
		foreach ($placeHolderIds as $placeHolderId) {
			foreach ($blocks as $block) {
				if ($block->getLocked() && $block->getPlaceHolder()->getId() === $placeHolderId) {
					$this->blockSet[] = $block;
				}
			}
		}

		// Collect all unlocked blocks
		/* @var $block Entity\Abstraction\Block */
		foreach ($blocks as $block) {
			if ( ! $block->getLocked()) {
				$this->blockSet[] = $block;
			}
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

		$em = $this->getDoctrineEntityManager();
		$qb = $em->createQueryBuilder();
		$expr = $qb->expr();
		$or = $expr->orX();

		$cnt = 0;

		$blockSet = $this->getBlockSet();
		
		$sharedPropertyFinder = new SharedPropertyFinder($em);
		
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
				$data = $this->getPageLocalization();
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

			$sharedPropertyFinder->addBlock($block, $data);
		}
		
		$result = array();
		
		// Load only if any condition is added to the query
		if ($cnt != 0) {
			$qb->select('bp')
					->from(BlockProperty::CN(), 'bp')
					->where($or);
			$query = $qb->getQuery();

			\Log::debug("Running query to find block properties");

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
		$sharedPropertyFinder->replaceInPropertySet($this->blockPropertySet);

		// Preload blockPropertyMetadata using single query for public requests to increase performance
		if ($this instanceof PageRequestView) {
			$this->preLoadPropertyMetadata();
		}

		return $this->blockPropertySet;
	}

	/**
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
	public function createMissingPlaceHolders()
	{
		$layoutPlaceHolderNames = $this->getLayoutPlaceHolderNames();

		if (empty($layoutPlaceHolderNames)) {
			return;
		}

		// getPlaceHolderSet() already contains current method call inside
		// but it should not go recursively, as getPlaceHolderSet() will return
		// set without executing, if it is already loaded
		$placeHolderSet = $this->getPlaceHolderSet();

		$entityManager = $this->getDoctrineEntityManager();
		$localization = $this->getPageLocalization();

		$finalPlaceHolders = $placeHolderSet->getFinalPlaceHolders();
		$parentPlaceHolders = $placeHolderSet->getParentPlaceHolders();

		foreach ($layoutPlaceHolderNames as $name) {
			if ( ! $finalPlaceHolders->offsetExists($name)) {


				// Check if page doesn't have it already set locally
				$placeHolder = null;
				$knownPlaceHolders = $localization->getPlaceHolders();

				if ($knownPlaceHolders->offsetExists($name)) {
					$placeHolder = $knownPlaceHolders->offsetGet($name);
				}

				if (empty($placeHolder)) {
					// Copy unlocked blocks from the parent template
					$parentPlaceHolder = $parentPlaceHolders->getLastByName($name);

					$placeHolder = Entity\Abstraction\PlaceHolder::factory($localization, $name, $parentPlaceHolder);
					$placeHolder->setMaster($localization);
				}

				// Persist only for draft connection with ID generation
				if ($this instanceof PageRequestEdit) {
					$entityManager->persist($placeHolder);
				}

				$placeHolderSet->append($placeHolder);
			}
		}

		// Flush only for draft connection with ID generation
		if ($this instanceof PageRequestEdit && $this->allowFlushing) {
			$entityManager->flush();
		}
	}

}
