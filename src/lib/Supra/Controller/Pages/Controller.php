<?php

namespace Supra\Controller\Pages;

use Supra\Controller\ControllerAbstraction,
		Supra\Controller\Response,
		Supra\Controller\Request,
		Supra\Controller\Layout,
		Supra\Database\Doctrine,
		Supra\Locale\Data as LocaleData,
		Doctrine\ORM\PersistentCollection,
		Doctrine\ORM\Query\Expr,
		Closure;

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
	 * Page data class to be used
	 * @var string
	 */
	const PAGE_DATA_ENTITY = 'Supra\Controller\Pages\Entity\PageData';

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
	const BLOCK_PROPERTY_ENTITY = 'Supra\Controller\Pages\Entity\Abstraction\BlockProperty';

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
	public function  __construct()
	{
		$this->setLocale();
		$this->setMedia();
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
	protected function setLocale()
	{
		$this->locale = LocaleData::getInstance()->getCurrent();
	}

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
		$masters = $this->collectMasterNodes();
		
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
		
		$this->prepareBlockControllers($blocks);
		\Log::sdebug("Blocks prepared for {$page}");

		$this->outputBlockControllers($blocks);
		\Log::sdebug("Blocks executed for {$page}");

		$placeResponses = $this->getPlaceResponses($blocks);

		$this->processLayout($layout, $placeResponses);
		\Log::sdebug("Layout {$layout} processed and output to response for {$page}");

	}

	/**
	 * Collects template/page hierarchy array
	 * @return array
	 */
	protected function collectMasterNodes()
	{
		$page = $this->getRequestPage();
		\Log::sdebug('Found page #', $page->getId());

		$templates = $page->getTemplates();
		if (empty($templates[0])) {
			throw new Exception('Response from getTemplates should contain at least 1 template for page #' . $page->getId());
		}
		$masters = $templates;
		$masters[] = $page;
		return $masters;
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
	 * Output method
	 */
	public function output()
	{
		//$this->getResponse()->output('So far so good');
	}

	/**
	 * Generate response object
	 * @param Request\RequestInterface
	 * @return Response\Http
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		return new Response\Http();
	}

	/**
	 * Get request page by current action
	 * @return Page
	 * @throws Exception
	 */
	protected function getRequestPage()
	{
		$action = $this->request->getActionString();
		$action = trim($action, '/');

		$em = $this->getDoctrineEntityManager();
		$er = $em->getRepository(static::PAGE_DATA_ENTITY);

		$searchCriteria = array(
			'locale' => $this->locale,
			'path' => $action,
		);

		//TODO: think about "enable path params" feature
		
		/* @var $page Entity\PageData */
		$pageData = $er->findOneBy($searchCriteria);

		if (empty($pageData)) {
			//TODO: 404 page
			throw new Exception("No page found by path '$action' in pages controller");
		}

		return $pageData->getPage();
	}

	/**
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
		$places = $query->getResult();

		\Log::sdebug('Count of place holders found: ' . count($places));

		return $places;
	}


	/**
	 * Search blocks inside the place holders
	 * @param array $places
	 * @param Entity\Abstraction\Page $finalNode
	 * @return array of blocks
	 */
	protected function findBlocks($places, $finalNode)
	{
		$em = $this->getDoctrineEntityManager();
		
		/*
		 * @var $finalPlaceHolderIds array
		 * The list of final (locked or belongs to the final master) placeholder ids.
		 * The block list will be taken from these placeholders.
		 */
		$finalPlaceHolderIds = array();

		/*
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
		$addBlock = function($block) use (&$blocksByPlaceHolderName) {
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
	protected function prepareBlockControllers(array &$blocks)
	{
		$request = $this->getRequest();

		// function which adds controllers for the block
		$prepare = function(Entity\Abstraction\Block $block) use ($request) {
			$blockController = $block->getController();
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
			$blockController->output();
			return $block;
		};
		
		// Iterates through all blocks and calls the function passed
		$this->iterateBlocks($blocks, $prepare);
	}

	/**
	 * Iterates through blocks and returs array of place holder responses
	 * @param array $blocks
	 * @return array
	 */
	protected function getPlaceResponses(array &$blocks)
	{
		$placeResponses = array();

		$collectResponses = function(Entity\Abstraction\Block $block, $placeName) use (&$placeResponses) {
			$response = $block->getController()->getResponse();
			if ( ! isset($placeResponses[$placeName])) {
				$placeResponses[$placeName] = $response;
			} else {
				$response->flushToResponse($placeResponses[$placeName]);
			}
		};

		// Iterates through all blocks and collects placeholder responses
		$this->iterateBlocks($blocks, $collectResponses);

		return $placeResponses;
	}

	/**
	 * Iteration funciton for specific array of blocks
	 * @param array $blocks
	 * @param Closure $function
	 */
	protected function iterateBlocks(array &$blocks, Closure $function)
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

		$locale = $this->locale;

		$collectCondition = function($block) use (&$cnt, $qb, $expr, &$or, $finalNode, $locale) {
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
				\Log::swarn("The data record has not been found for page {$master} locale {$this->locale}, will not fill block parameters");
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
		
		/* @var $blockProperty Entity\Abstraction\BlockProperty */
		foreach ($result as $blockProperty) {
			$block = $blockProperty->getBlock();
			$block->getController()->addProperty($blockProperty);
		}
	}

}