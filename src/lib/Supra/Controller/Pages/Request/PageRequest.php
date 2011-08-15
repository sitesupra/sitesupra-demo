<?php

namespace Supra\Controller\Pages\Request;

use Supra\Request\HttpRequest;
use Supra\Controller\Pages\Entity;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\Set;

/**
 * Page controller request
 */
abstract class PageRequest extends HttpRequest
{
	/**
	 * @var string
	 */
	const PAGE_ABSTRACT_ENTITY = 'Supra\Controller\Pages\Entity\Abstraction\Page';
	/**
	 * Page class name
	 * @var string
	 */
	const PAGE_ENTITY = 'Supra\Controller\Pages\Entity\Page';
	
	/**
	 * Data abstraction class name
	 * @var string
	 */
	const DATA_ENTITY = 'Supra\Controller\Pages\Entity\Abstraction\Data';
	
	/**
	 * Page data class name
	 * @var string
	 */
	const PAGE_DATA_ENTITY = 'Supra\Controller\Pages\Entity\PageData';
	
	/**
	 * Template class name
	 * @var string
	 */
	const TEMPLATE_ENTITY = 'Supra\Controller\Pages\Entity\Template';
	
	/**
	 * Page data class name
	 * @var string
	 */
	const TEMPLATE_DATA_ENTITY = 'Supra\Controller\Pages\Entity\TemplateData';

	/**
	 * Block abstraction class name
	 * @var string
	 */
	const BLOCK_ENTITY = 'Supra\Controller\Pages\Entity\Abstraction\Block';

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
	 * @var Entity\Abstraction\Data
	 */
	private $requestPageData;
	
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
	private $placeHolderSet;
	
	/**
	 * @var Set\BlockSet
	 */
	private $blockSet;
	
	/**
	 * @var BlockPropertySet
	 */
	private $blockPropertySet;
	
	/**
	 * @param string $locale
	 * @param string $media 
	 */
	public function __construct($locale, $media = Entity\Layout::MEDIA_SCREEN)
	{
		$this->locale = $locale;
		$this->media = $media;
		
		parent::__construct();
	}
	
	/**
	 * @return Entity\Abstraction\Data
	 */
	public function getRequestPageData()
	{
		return $this->requestPageData;
	}
	
	/**
	 * @param Entity\Abstraction\Data $requestPageData
	 */
	public function setRequestPageData(Entity\Abstraction\Data $requestPageData)
	{
		$this->requestPageData = $requestPageData;
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
	 * @return Entity\Abstraction\Page 
	 */
	public function getPage()
	{
		return $this->getRequestPageData()
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
		$this->pageSet = $this->getPage()
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
		
		$this->layout = $this->getRootTemplate()
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
		
		$page = $this->getPage();
		$this->placeHolderSet = new Set\PlaceHolderSet($page);
		
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
				->join('ph.master', 'm')
				->where($qb->expr()->in('ph.name', $layoutPlaceHolderNames))
				->andWhere($qb->expr()->in('m.id', $pageSetIds))
				// templates first (type: 0-templates, 1-pages)
				->orderBy('ph.type', 'ASC')
				->addOrderBy('m.level', 'ASC');
		
		$query = $qb->getQuery();
		$placeHolderArray = $query->getResult();
		
		foreach ($placeHolderArray as $placeHolder) {
			/* @var $place PlaceHolder */
			$this->placeHolderSet->append($placeHolder);
		}
		
		// Create missing place holders automatically
		$layoutPlaceHolderNames = $this->getLayoutPlaceHolderNames();
		$finalPlaceHolders = $this->placeHolderSet->getFinalPlaceHolders();
		$parentPlaceHolders = $this->placeHolderSet->getParentPlaceHolders();
		
		foreach ($layoutPlaceHolderNames as $name) {
			if ( ! $finalPlaceHolders->offsetExists($name)) {
				$placeHolder = Entity\Abstraction\PlaceHolder::factory($page, $name);
				$placeHolder->setMaster($page);
				
				// Copy unlocked blocks from the parent template
				$parentPlaceHolder = $parentPlaceHolders->getLastByName($name);
				
				if ( ! is_null($parentPlaceHolder)) {
					$blocks = $parentPlaceHolder->getBlocks();
					
					/* @var $block Entity\Abstraction\Block */
					foreach ($blocks as $block) {
						
						if ($block->getLocked()) {
							continue;
						}
						
						// Create new block
						$block = Entity\Abstraction\Block::factoryClone($page, $block);
						$placeHolder->addBlock($block);
						
						$blockProperties = $block->getBlockProperties();
						$data = $this->getRequestPageData();
						
						/* @var $blockProperty Entity\BlockProperty */
						foreach ($blockProperties as $blockProperty) {
							$blockProperty->setData($data);
						}
					}
				}
				
				$em->persist($placeHolder);
				$this->placeHolderSet->append($placeHolder);
			}
		}
		
		$em->flush();
		
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

		// final placeholder blocks
		if ( ! empty($finalPlaceHolderIds)) {
			$qb->orWhere($expr->in('ph.id', $finalPlaceHolderIds));
		}
		
		// locked block condition
		if ( ! empty($parentPlaceHolderIds)) {
			$lockedBlocksCondition = $expr->andX(
					$expr->in('ph.id', $parentPlaceHolderIds),
					'b.locked = TRUE'
			);
			$qb->orWhere($lockedBlocksCondition);
		}
		
		// Execute block query
		$blocks = $qb->getQuery()->getResult();

		\Log::debug("Block count found: " . count($blocks));

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
		
		$page = $this->getPage();

		// Loop generates condition for 
		foreach ($blockSet as $block) {
			$master = null;
			
			if ($block->getLocked()) {
				$master = $block->getPlaceHolder()
						->getMaster();
			} else {
				$master = $page;
			}
			
			\Log::debug("Master node for {$block} is found - {$master}");
			
			// FIXME: n+1 problem
			$data = $master->getData($this->locale);
			
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
			$and->add($expr->eq('bp.data', '?' . (++$cnt)));
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
		
		return $this->blockPropertySet;
	}

}
