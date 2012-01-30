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

/**
 * Page controller request
 */
abstract class PageRequest extends HttpRequest
{
	/**
	 * @var string
	 */
	const PAGE_ABSTRACT_ENTITY = 'Supra\Controller\Pages\Entity\Abstraction\AbstractPage';
	/**
	 * Page class name
	 * @var string
	 */
	const PAGE_ENTITY = 'Supra\Controller\Pages\Entity\Page';

	/**
	 * Data abstraction class name
	 * @var string
	 */
	const DATA_ENTITY = 'Supra\Controller\Pages\Entity\Abstraction\Localization';

	/**
	 * Page data class name
	 * @var string
	 */
	const PAGE_DATA_ENTITY = 'Supra\Controller\Pages\Entity\PageLocalization';

	/**
	 * Template class name
	 * @var string
	 */
	const TEMPLATE_ENTITY = 'Supra\Controller\Pages\Entity\Template';

	/**
	 * Page data class name
	 * @var string
	 */
	const TEMPLATE_DATA_ENTITY = 'Supra\Controller\Pages\Entity\TemplateLocalization';

	/**
	 * Block abstraction class name
	 * @var string
	 */
	const BLOCK_ENTITY = 'Supra\Controller\Pages\Entity\Abstraction\Block';

	/**
	 * Template block class name
	 * @var string
	 */
	const TEMPLATE_BLOCK_ENTITY = 'Supra\Controller\Pages\Entity\TemplateBlock';

	/**
	 * Block abstraction class name
	 * @var string
	 */
	const PLACE_HOLDER_ENTITY = 'Supra\Controller\Pages\Entity\Abstraction\PlaceHolder';

	/**
	 * Block abstraction class name
	 * @var string
	 */
	const BLOCK_PROPERTY_ENTITY = 'Supra\Controller\Pages\Entity\BlockProperty';

	/**
	 * Revision data class name
	 * @var string
	 */
	const REVISION_DATA_ENTITY = 'Supra\Controller\Pages\Entity\PageRevisionData';

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
	private $media = Entity\Layout::MEDIA_SCREEN;

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
	 * @var Entity\Layout
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
	 * @param string $locale
	 * @param string $media
	 */
	public function __construct($locale, $media = Entity\Layout::MEDIA_SCREEN)
	{
		parent::__construct();
		
		$this->locale = $locale;
		$this->media = $media;
		$this->log = ObjectRepository::getLogger($this);
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
		return $this->getPageLocalization()
						->getMaster();
	}

	/**
	 * @return Set\PageSet
	 */
	public function getPageSet()
	{
		if (isset($this->pageSet)) {
			return $this->pageSet;
		}

		// Fetch page/template hierarchy list
		$this->pageSet = $this->getPageLocalization()
				->getTemplateHierarchy();

		return $this->pageSet;
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
	 * @return Entity\Layout
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
	 * @return array
	 */
	public function getLayoutPlaceHolderNames()
	{
		return $this->getLayout()
						->getPlaceHolderNames();
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

		// Nothing to search for
		if (empty($pageSetIds) || empty($layoutPlaceHolderNames)) {

			return $this->placeHolderSet;
		}

		// Find template place holders
		$qb = $em->createQueryBuilder();

		$qb->select('ph')
				->from(static::PLACE_HOLDER_ENTITY, 'ph')
				->join('ph.localization', 'pl')
				->join('pl.master', 'p')
				->where($qb->expr()->in('ph.name', $layoutPlaceHolderNames))
				->andWhere($qb->expr()->in('p.id', $pageSetIds))
				->andWhere('pl.locale = ?0')
				->setParameter(0, $localeId)
				// templates first (type: 0-templates, 1-pages)
				->orderBy('ph.type', 'ASC')
				->addOrderBy('p.level', 'ASC');

		$query = $qb->getQuery();
		$placeHolderArray = $query->getResult();

		foreach ($placeHolderArray as $placeHolder) {
			/* @var $place PlaceHolder */
			$this->placeHolderSet->append($placeHolder);
		}

		
		// Create missing place holders automatically
		$finalPlaceHolders = $this->placeHolderSet->getFinalPlaceHolders();
		$parentPlaceHolders = $this->placeHolderSet->getParentPlaceHolders();

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
					$em->persist($placeHolder);
				}

				$this->placeHolderSet->append($placeHolder);
			}
		}

		// Flush only for draft connection with ID generation
		if ($this instanceof PageRequestEdit && $this->allowFlushing) {
			$em->flush();
		}

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

		$placeHolderSet = $this->getPlaceHolderSet();

		$finalPlaceHolderIds = $placeHolderSet->getFinalPlaceHolders()
				->collectIds();

		$parentPlaceHolderIds = $placeHolderSet->getParentPlaceHolders()
				->collectIds();

		// Just return empty array if no final/parent place holders have been found
		if (empty($finalPlaceHolderIds) && empty($parentPlaceHolderIds)) {
			return $this->blockSet;
		}

		// Here we find all 1) locked blocks from templates; 2) all blocks from final place holders
		$qb = $em->createQueryBuilder();
		$qb->select('b')
				->from(static::BLOCK_ENTITY, 'b')
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

		// Execute block query
		$query = $qb->getQuery();
		$query->execute();
		$blocks = $query->getResult();

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
		 * Collect locked blocks from not final placesholders
		 * these are positioned as first blocks in the placeholder
		 */
		/* @var $block Entity\Abstraction\Block */
		foreach ($blocks as $block) {
			if ($block->inPlaceHolder($parentPlaceHolderIds)) {
				$this->blockSet[] = $block;
			}
		}

		// Collect all blocks from final placeholders
		/* @var $block Entity\Abstraction\Block */
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
	public function getBlockPropertySet($em = null)
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

		$page = $this->getPage();

		// Loop generates condition for property getter
		foreach ($blockSet as $block) {
			/* @var $block Entity\Abstraction\Block */

			$master = null;

			if ($block->getLocked()) {
				$master = $block->getPlaceHolder()
						->getMaster()
						->getMaster();
			}
			else {
				$master = $page;
			}

			\Log::debug("Master node for {$block} is found - {$master}");

			// FIXME: n+1 problem
			$data = $master->getLocalization($this->locale);

			if (empty($data)) {
				\Log::warn("The data record has not been found for page {$master} locale {$this->locale}, will not fill block parameters");
				$blockSet->removeInvalidBlock($block, "Page data for locale not found");
				continue;
			}

			$blockId = $block->getId();
			$dataId = $data->getId();

			$and = $expr->andX();
			$and->add($expr->eq('bp.block', '?' . ( ++ $cnt)));
			$qb->setParameter($cnt, $blockId);
			$and->add($expr->eq('bp.localization', '?' . ( ++ $cnt)));
			$qb->setParameter($cnt, $dataId);

			$or->add($and);
			\Log::debug("Have generated condition for properties fetch for block $block");
		}

		// Stop if no propereties were found
		if ($cnt == 0) {
			return $this->blockPropertySet;
		}

		$qb->select('bp')
				->from(static::BLOCK_PROPERTY_ENTITY, 'bp')
				->where($or);
		$query = $qb->getQuery();

		\Log::debug("Running query {$qb->getDQL()} to find block properties");

		$result = $query->getResult();

		$this->blockPropertySet->exchangeArray($result);

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
						->where($qb->expr()->in('l.master', $elementPageIds));
				
				$localizations = $qb->getQuery()
						->getResult();
				
				foreach($localizations as $pageLocalization) {
					$entityData = $em->getUnitOfWork()
							->getOriginalEntityData($pageLocalization);
					
					$localizationIds[] = $entityData['master_id'];
				}
				
				$localizations = array_combine($localizationIds, $localizations);
				
				foreach($referencedElements as $element) {
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
//				$propertyId = $property->getId();
				
				$propertyData = $em->getUnitOfWork()
						->getOriginalEntityData($propertyMetadata);
				
				if (isset($propertyData['referencedElement_id'])) {
					
					$elementId = $propertyData['referencedElement_id'];
					if (isset($referencedElements[$elementId])) {
						$propertyMetadata->setOverridenReferencedElement($referencedElements[$elementId]);
					}
				}

//				$property = $this->blockPropertySet->findById($propertyId);
//				if ( ! is_null($property)) {
					/* @var $property BlockProperty */
					$property->addOverridenMetadata($propertyMetadata);
//				}
			}
		}
		
	}

}
