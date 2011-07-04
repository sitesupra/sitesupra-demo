<?php

namespace Supra\Controller\Pages\Set;

use Supra\Controller\Pages\Entity,
		Doctrine\ORM\EntityManager,
		Supra\Controller\Pages\Exception;

/**
 * Set of objects used within the request
 */
class RequestSet
{
	/**
	 * Page class to be used
	 * FIXME: Not used now
	 * @var string
	 */
	const PAGE_ENTITY = 'Supra\Controller\Pages\Entity\Page';
	
	/**
	 * Template class to be used
	 * FIXME: Not used now
	 * @var string
	 */
	const TEMPLATE_ENTITY = 'Supra\Controller\Pages\Entity\Template';

	/**
	 * Block abstraction class to be used
	 * @var string
	 */
	const BLOCK_ENTITY = 'Supra\Controller\Pages\Entity\Abstraction\Block';

	/**
	 * Block abstraction class to be used
	 * @var string
	 */
	const PLACE_HOLDER_ENTITY = 'Supra\Controller\Pages\Entity\Abstraction\PlaceHolder';

	/**
	 * Block abstraction class to be used
	 * @var string
	 */
	const BLOCK_PROPERTY_ENTITY = 'Supra\Controller\Pages\Entity\BlockProperty';
	
	/**
	 * @var EntityManager
	 */
	private $em;
	
	/**
	 * @var string
	 */
	private $media;

	/**
	 * @var string
	 */
	private $locale;
	
	/**
	 * @var Entity\Abstraction\Page
	 */
	private $page;
	
	/**
	 * @var PageSet
	 */
	private $pageSet;

	/**
	 * @var Entity\Layout
	 */
	private $layout;
	
	/**
	 * @var PlaceHolderSet
	 */
	private $placeHolderSet;
	
	/**
	 * @var BlockSet
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
	public function __construct($locale, $media)
	{
		$this->locale = $locale;
		$this->media = $media;
	}
	
	/**
	 * @return EntityManager 
	 */
	public function getDoctrineEntityManager()
	{
		return $this->em;
	}

	/**
	 * @param EntityManager $em
	 */
	public function setDoctrineEntityManager(EntityManager $em)
	{
		$this->em = $em;
	}
	
	/**
	 * @return Entity\Abstraction\Page 
	 */
	public function getPage()
	{
		return $this->page;
	}

	/**
	 * @param Entity\Abstraction\Page $page
	 */
	public function setPage(Entity\Abstraction\Page $page)
	{
		$this->page = $page;
	}
	
	/**
	 * @return PageSet
	 */
	public function getPageSet()
	{
		if (isset($this->pageSet)) {
			return $this->pageSet;
		}
		
		// Fetch page/template hierarchy list
		$this->pageSet = $this->page->getTemplateHierarchy();
		
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
	 * @return PlaceHolderSet
	 */
	public function getPlaceHolderSet()
	{
		if (isset($this->placeHolderSet)) {
			return $this->placeHolderSet;
		}
		
		$this->placeHolderSet = new PlaceHolderSet($this->getPage());
		
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
				->addOrderBy('m.depth', 'ASC');
		
		$query = $qb->getQuery();
		$placeHolderArray = $query->getResult();
		
		//TODO: create missing place holders automatically, copy unlocked blocks from the parent template
		foreach ($placeHolderArray as $placeHolder) {
			/* @var $place PlaceHolder */
			$this->placeHolderSet->append($placeHolder);
		}
		
		\Log::sdebug('Count of place holders found: ' . count($this->placeHolderSet));
		
		return $this->placeHolderSet;
	}
	
	/**
	 * @return BlockSet
	 */
	public function getBlockSet()
	{
		if (isset($this->blockSet)) {
			return $this->blockSet;
		}
		
		$em = $this->getDoctrineEntityManager();
		$this->blockSet = new BlockSet();
		
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

		\Log::sdebug("Block count found: " . count($blocks));

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
	 * @return BlockPropertySet
	 */
	public function getBlockPropertySet()
	{
		if (isset($this->blockPropertySet)) {
			return $this->blockPropertySet;
		}
		
		$this->blockPropertySet = new BlockPropertySet();
		
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
			
			\Log::sdebug("Master node for {$block} is found - {$master}");
			
			// FIXME: n+1 problem
			$data = $master->getData($this->locale);
			
			if (empty($data)) {
				\Log::swarn("The data record has not been found for page {$master} locale {$this->locale}, will not fill block parameters");
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
			\Log::sdebug("Have generated condition for properties fetch for block $block");
		}

		// Stop if no propereties were found
		if ($cnt == 0) {
			return $this->blockPropertySet;
		}

		$qb->select('bp')
				->from(static::BLOCK_PROPERTY_ENTITY, 'bp')
				->where($or);
		$query = $qb->getQuery();
		
		\Log::sdebug("Running query {$qb->getDQL()} to find block properties");

		$result = $query->getResult();
		
		$this->blockPropertySet->exchangeArray($result);
		
		return $this->blockPropertySet;
	}
	
}
