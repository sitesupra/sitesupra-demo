<?php

namespace Supra\Controller\Pages;

use Supra\Controller\ControllerAbstraction,
		Supra\Response,
		Supra\Response\ResponseInterface,
		Supra\Request\RequestInterface,
		Supra\Controller\Layout,
		Supra\Database\Doctrine,
		Supra\Locale\Data as LocaleData,
		Doctrine\ORM\PersistentCollection,
		Doctrine\ORM\Query\Expr,
		Supra\Controller\NotFoundException,
		Supra\Controller\Pages\Request\HttpEditRequest,
		Supra\Controller\Pages\Response\PlaceHolder as PlaceHolderResponse;

/**
 * Page controller
 */
class Controller extends ControllerAbstraction
{

	/**
	 * Page class to be used
	 * FIXME: Not used now
	 * @var string
	 */
	const PAGE_ENTITY = 'Supra\Controller\Pages\Entity\Page';

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
	 * Current locale, set on execute start
	 * @var string
	 */
	protected $locale;

	/**
	 * Current media type
	 * @var string
	 */
	protected $media = 'screen';
	
	/**
	 * Construct
	 */
	public function __construct()
	{
		$this->setMedia();
	}
	
	/**
	 * Downcasts receives request object into 
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	public function prepare(RequestInterface $request, ResponseInterface $response)
	{
		// Downcast to local request object
		if ( ! $request instanceof namespace\Request\Request) {
			$request = new namespace\Request\RequestView($request);
		}
		
		$em = $this->getDoctrineEntityManager();
		$request->setDoctrineEntityManager($em);
		
		parent::prepare($request, $response);
	}
	
	/**
	 * Overriden to specify correct return class
	 * @return \Supra\Controller\Pages\Request\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	protected function getDoctrineEntityManager()
	{
		$em = Doctrine::getInstance()->getEntityManager();
		
		return $em;
	}

	/**
	 * Sets current locale
	 */
//	protected function setLocale()
//	{
//		$this->locale = LocaleData::getInstance()->getCurrent();
//	}

	/**
	 * Sets current media
	 */
	protected function setMedia()
	{
		$this->media = 'screen';
	}
	
	/**
	 * Execute controller
	 */
	public function execute()
	{
		// fetch page/template hierarchy list
		$masters = $this->collectPageHierarchy();
		
		/* @var $masterIds int[] */
		$masterIds = Entity\Abstraction\Entity::collectIds($masters);
		\Log::sdebug('Found these pages/templates: ', implode(', ', $masterIds));

		/* @var $rootTemplate Entity\Template */
		$rootTemplate = $masters[0];

		/* @var $page Entity\Page */
		$page = array_pop($masters);

		\Log::sdebug("Root template #{$rootTemplate->getId()} found for page #{$page->getId()}");

		/* @var $layout Entity\Layout */
		$layout = $rootTemplate->getLayout($this->media);
		if (empty($layout)) {
			throw new Exception("No layout defined for template #{$rootTemplate->getId()} media {$this->media}");
		}
		\Log::sdebug("Root template {$rootTemplate->getId()} has layout {$layout->getFile()} for media {$this->media}");

		$layoutPlaceNames = $layout->getPlaceHolderNames();
		\Log::sdebug('Layout place holder names: ', $layoutPlaceNames);

		// find place holders
		$places = $this->findPlaceHolders($masterIds, $layoutPlaceNames);

		// find blocks organized by place holder name
		$blocks = $this->findBlocks($places, $page);

		$this->getBlockControllers($blocks);
		\Log::sdebug("Block controllers created for {$page}");
		
		$this->collectBlockProperties($blocks, $page);
		\Log::sdebug("Block properties collected for {$page}");
		
		$this->prepareBlockControllers($blocks, $page);
		\Log::sdebug("Blocks prepared for {$page}");

		$this->outputBlockControllers($blocks);
		\Log::sdebug("Blocks executed for {$page}");

		$placeResponses = $this->getPlaceResponses($places, $blocks, $page);

		$this->processLayout($layout, $placeResponses);
		\Log::sdebug("Layout {$layout} processed and output to response for {$page}");

	}

	/**
	 * Collects template/page hierarchy array
	 * @return array
	 */
	protected function collectPageHierarchy()
	{
		$page = $this->getRequest()
				->getRequestPageData()
				->getMaster();
		
		\Log::sdebug('Found page #', $page->getId());

		$hierarchy = $page->getHierarchy();
		
		return $hierarchy;
	}

	/**
	 * @param Entity\Layout $layout
	 * @param array $blocks array of block responses
	 */
	protected function processLayout(Entity\Layout $layout, array $placeResponses)
	{
		$layoutProcessor = $this->getLayoutProcessor();
		$layoutSrc = $layout->getFile();
		$response = $this->getResponse();
		$layoutProcessor->process($response, $placeResponses, $layoutSrc);
	}

	/**
	 * @return Layout\Processor\ProcessorInterface
	 */
	protected function getLayoutProcessor()
	{
		$processor = new Layout\Processor\Html();
		$processor->setLayoutDir(\SUPRA_PATH . 'template');
		return $processor;
	}

	/**
	 * Generate response object
	 * @param RequestInterface
	 * @return Response\Http
	 */
	public function createResponse(RequestInterface $request)
	{
		return new Response\Http();
	}

	/**
	 * Finds all place holders we are interested in, creates missing holders
	 * @param array $masterIds
	 * @param array $layoutPlaceNames
	 * @return array of placeholders
	 */
	protected function findPlaceHolders(array $masterIds, array $layoutPlaceNames)
	{
		$em = $this->getDoctrineEntityManager();
		
		if (empty($masterIds) || empty($layoutPlaceNames)) {
			return array();
		}
		
		// Find template place holders
		$qb = $em->createQueryBuilder();

		$qb->select('ph')
				->from(static::PLACE_HOLDER_ENTITY, 'ph')
				->join('ph.master', 'm')
				->where($qb->expr()->in('ph.name', $layoutPlaceNames))
				->andWhere($qb->expr()->in('m.id', $masterIds))
				// templates first (type: 0-templates, 1-pages)
				->orderBy('ph.type', 'ASC')
				->addOrderBy('m.depth', 'ASC');
		
		$query = $qb->getQuery();
		$allPlaces = $query->getResult();
		
		$places = array();
		$lockedPlaces = array();
		
		foreach ($allPlaces as $place) {
			/* @var $place PlaceHolder */
			
			$name = $place->getName();
			
			// Skipping already locked places
			if (array_key_exists($name, $lockedPlaces)) {
				continue;
			}
			
			if ($place->getLocked()) {
				$lockedPlaces[$name] = true;
			}
			
			$places[] = $place;
		}
		
		//TODO: create missing place holders automatically, copy unlocked blocks from the parent template
		
		\Log::sdebug('Count of place holders found: ' . count($places));

		return $places;
	}


	/**
	 * Search blocks inside the place holders
	 * @param array $places
	 * @param Entity\Abstraction\Page $finalNode
	 * @return array of blocks
	 */
	protected function findBlocks(array $places, Entity\Abstraction\Page $finalNode)
	{
		$em = $this->getDoctrineEntityManager();
		
		/**
		 * @var $finalPlaceHolderIds array
		 * The list of final (locked or belongs to the final master) placeholder ids.
		 * The block list will be taken from these placeholders.
		 */
		$finalPlaceHolderIds = array();

		/**
		 * @var $parentPlaceHolderIds array
		 * The list of placeholder ids which are parents of final placeholders.
		 * The locked blocks will be searched within these placeholders.
		 *
		 * FIXME: can remove this and further usages and functionality
		 *		if blocks can't be locked inside unlocked placeholders
		 */
		$parentPlaceHolderIds = array();

		/* @var $place Entity\Abstraction\PlaceHolder */
		foreach ($places as $place) {

			$name = $place->getName();
			$id = $place->getId();

			// Don't overwrite if final place holder already found
			if (isset($finalPlaceHolderIds[$name])) {
				continue;
			}
			
			// add only in cases when it's the page place or locked one
			if ($place->getMaster() == $finalNode || $place->getLocked()) {
				$finalPlaceHolderIds[$name] = $id;
			} else {
				// collect not matched template place holders to search for locked blocks
				$parentPlaceHolderIds[] = $id;
			}
		}

		// Just return empty array if no final/parent place holders have been found
		if (empty($finalPlaceHolderIds) && empty($parentPlaceHolderIds)) {
			return array();
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
			$lockedBlocksCondition = $expr->andX()
					->addMultiple(array(
						$expr->in('ph.id', $parentPlaceHolderIds),
						'b.locked = TRUE'
					));
			$qb->orWhere($lockedBlocksCondition);
		}

		// Execute block query
		$blocks = $qb->getQuery()->getResult();

		\Log::sdebug("Block count found: " . count($blocks));

		$blocksByPlaceHolderName = array();

		// Helper function to add block to the final array
		$addBlock = function(Entity\Abstraction\Block $block) use (&$blocksByPlaceHolderName) {
			$name = $block->getPlaceHolder()->getName();
			if ( ! isset($blocksByPlaceHolderName[$name])) {
				$blocksByPlaceHolderName[$name] = array();
			}
			$blocksByPlaceHolderName[$name][] = $block;
		};

		/*
		 * Collect locked blocks from not final placesholders
		 * these are positioned as first blocks in the placeholder
		 */
		/* @var $block Entity\Abstraction\Block */
		foreach ($blocks as $block) {
			if ($block->inPlaceHolder($parentPlaceHolderIds)) {
				$addBlock($block);
			}
		}

		// Collect all blocks from final placeholders
		/* @var $block Entity\Abstraction\Block */
		foreach ($blocks as $block) {
			if ($block->inPlaceHolder($finalPlaceHolderIds)) {
				$addBlock($block);
			}
		}

		return $blocksByPlaceHolderName;
	}

	/**
	 * Create block controllers
	 * @param array $blocks
	 */
	protected function getBlockControllers(array &$blocks)
	{
		// function which adds controllers for the block
		$controllerFactory = function(Entity\Abstraction\Block $block) {
			$blockController = $block->controllerFactory();
			
			if (empty($blockController)) {
				throw new SkipBlockException('Block controller was not found');
			}
			$block->setController($blockController);
		};

		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($blocks, $controllerFactory);
	}

	/**
	 * @param array $blocks
	 */
	protected function prepareBlockControllers(array &$blocks, Entity\Abstraction\Page $page)
	{
		$request = $this->getRequest();

		// function which adds controllers for the block
		$prepare = function(Entity\Abstraction\Block $block) use ($page, $request) {
			$blockController = $block->getController();
			$blockController->setPage($page);
			$blockResponse = $blockController->createResponse($request);
			$blockController->prepare($request, $blockResponse);
			
			return $block;
		};

		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($blocks, $prepare);
	}

	/**
	 * @param array $blocks
	 */
	protected function outputBlockControllers(array &$blocks)
	{
		// function which adds controllers for the block
		$prepare = function(Entity\Abstraction\Block $block) {
			$blockController = $block->getController();
			$blockController->execute();
			return $block;
		};
		
		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($blocks, $prepare);
	}
	
	/**
	 * Creates place holder response object
	 * @param Entity\Abstraction\Page $page
	 * @param Entity\Abstraction\PlaceHolder $placeHolder
	 * @return PlaceHolderResponse\Response
	 */
	public function createPlaceResponse(Entity\Abstraction\Page $page, Entity\Abstraction\PlaceHolder $placeHolder)
	{
		$response = null;
		
		// TODO: create edit response for unlocked place holders ONLY
		if ($this->request instanceof namespace\Request\RequestEdit
				&& $page->isPlaceHolderEditable($placeHolder)) {
			$response = new PlaceHolderResponse\ResponseEdit();
		} else {
			$response = new PlaceHolderResponse\ResponseView();
		}
		
		$response->setPlaceHolder($placeHolder);
		
		return $response;
	}

	/**
	 * Iterates through blocks and returs array of place holder responses
	 * @param array $blocks
	 * @return array
	 */
	protected function getPlaceResponses(array $places, array &$blocks, Entity\Abstraction\Page $page)
	{
		/* @var $finalPlacesByName array */
		$finalPlacesByName = array();
		
		/* @var $place Entity\Abstraction\PlaceHolder */
		foreach ($places as $place) {
			$name = $place->getName();
			$finalPlacesByName[$name] = $place;
		}
		
		$placeResponses = array();
		$controller = $this;

		$collectResponses = function(Entity\Abstraction\Block $block, $placeName) 
				use (&$placeResponses, $controller, &$page, $finalPlacesByName) {
			
			$response = $block->getController()->getResponse();
			
			if ( ! isset($placeResponses[$placeName])) {
				
				if ( ! isset($finalPlacesByName[$placeName])) {
					//TODO: what is the action on such case?
					throw new Exception("Logic problem – final place holder by name $placeName is not found");
				}
				
				// Get place holder object
				$placeHolder = $finalPlacesByName[$placeName];
				
				$placeResponse = $controller->createPlaceResponse($page, $placeHolder);
				
				
//				if ($page->isPlaceHolderEditable($placeHolder)) {
//					$placeResponse->setPlaceHolder($placeHolder);
//				}
				
				$placeResponses[$placeName] = $placeResponse;
			}
			
			$response->flushToResponse($placeResponses[$placeName]);
		};

		// Iterates through all blocks and collects placeholder responses
		$this->iterateBlocks($blocks, $collectResponses);

		return $placeResponses;
	}

	/**
	 * Iteration funciton for specific array of blocks
	 * @param array $blocks
	 * @param \Closure $function
	 */
	protected function iterateBlocks(array &$blocks, \Closure $function)
	{
		/* @var $blockList array */
		foreach ($blocks as $placeName => $blockList) {
			/* @var $block Entity\Abstraction\Block */
			foreach ($blockList as $blockKey => $block) {
				try {
					$result = $function($block, $placeName);
				} catch (SkipBlockException $e) {
					\Log::sdebug("Skipping block $block because of raised SkipBlockException: {$e->getMessage()}");
					unset($blocks[$placeName][$blockKey]);
				}
			}
		}
	}

	/**
	 * Finds block properties
	 * @param array $blocks
	 * @param Entity\Abstraction\Page $finalNode
	 */
	protected function collectBlockProperties(array &$blocks, Entity\Abstraction\Page $finalNode)
	{
		$em = $this->getDoctrineEntityManager();
		$qb = $em->createQueryBuilder();
		$expr = $qb->expr();
		$or = $expr->orX();

		$cnt = 0;

		$locale = $this->getRequest()
				->getLocale();

		$collectCondition = function(Entity\Abstraction\Block $block) use (&$cnt, $qb, $expr, &$or, $finalNode, $locale) {
			
			$master = null;
			
			if ($block->getLocked()) {
				$master = $block->getPlaceHolder()
						->getMaster();
			} else {
				$master = $finalNode;
			}
			
			\Log::sdebug("Master node for {$block} is found - {$master}");
			
			// FIXME: n+1 problem
			$data = $master->getData($locale);
			
			if (empty($data)) {
				\Log::swarn("The data record has not been found for page {$master} locale {$locale}, will not fill block parameters");
				throw new SkipBlockException('Page data for locale not found');;
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
		};

		$this->iterateBlocks($blocks, $collectCondition);

		// Stop if no propereties were found
		if ($cnt == 0) {
			return;
		}

		$qb->select('bp')
				->from(static::BLOCK_PROPERTY_ENTITY, 'bp')
				->where($or);
		$query = $qb->getQuery();
		
		\Log::sdebug("Running query {$qb->getDQL()} to find block properties");

		$result = $query->getResult();
		
		/* @var $blockProperty Entity\BlockProperty */
		foreach ($result as $blockProperty) {
			$block = $blockProperty->getBlock();
			$block->getController()->addProperty($blockProperty);
		}
	}

}