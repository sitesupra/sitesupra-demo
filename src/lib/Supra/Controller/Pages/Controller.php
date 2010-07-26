<?php

namespace Supra\Controller\Pages;

use Supra\Controller\ControllerAbstraction,
		Supra\Controller\Response,
		Supra\Controller\Request,
		Supra\Controller\Pages\Exception,
		Doctrine\ORM\PersistentCollection,
		Supra\Database\Doctrine,
		Supra\Locale\Data as LocaleData,
		Doctrine\ORM\Query\Expr;

/**
 * Page controller
 */
class Controller extends ControllerAbstraction
{

	/**
	 * Page class to be used
	 * @var string
	 */
	const PAGE_ENTITY = 'Supra\Controller\Pages\Entity\Page';

	/**
	 * Page data class to be used
	 * @var string
	 */
	const PAGE_DATA_ENTITY = 'Supra\Controller\Pages\Entity\PageData';

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
		$page = $this->getRequestPage();
		\Log::debug('Found page #', $page->getId());

		$templates = $page->getTemplates();
		if (empty($templates[0])) {
			throw new Exception('Response from getTemplates should contain at least 1 template for page #' . $page->getId());
		}
		/* @var $rootTemplate Entity\Template */
		$rootTemplate = $templates[0];

		\Log::debug("Root template #{$rootTemplate->getId()} found for page #{$page->getId()}");

		/* @var $layout Entity\Layout */
		$layout = $rootTemplate->getLayout($this->media);
		if (empty($layout)) {
			throw new Exception("No layout defined for template #{$rootTemplate->getId()}");
		}
		\Log::debug("Root template {$rootTemplate->getId()} has layout {$layout->getFile()} for media {$this->media}");

		$layoutPlaceHolderNames = $layout->getPlaceHolderNames();
		\Log::debug('Layout place holder names: ', $layoutPlaceHolderNames);

		/* @var $templateIds int[] */
		$templateIds = Entity\Abstraction\Entity::collectIds($templates);
		
		\Log::debug('Found these templates: ', implode(', ', $templateIds));

		$blockResponsesByPlace = array();

		// We need the further block processing if there are any place holders
		// in the layout
		if (count($layoutPlaceHolderNames) > 0) {
			
			$em = $this->getDoctrineEntityManager();

			/* @var $templatePageIds int[] */
			$templatePageIds = $templateIds;
			$templatePageIds[] = $page->getId();

			// Find template place holders
			$qb = $em->createQueryBuilder();

			$qb->select('ph')
					->from('Supra\Controller\Pages\Entity\Abstraction\PlaceHolder', 'ph')
					->where($qb->expr()->in('ph.name', $layoutPlaceHolderNames))
					->andWhere($qb->expr()->in('ph.master.id', $templatePageIds))
					// templates first (type: 0-templates, 1-pages)
					->orderBy('ph.type', 'ASC')
					->addOrderBy('ph.master.depth', 'ASC');
			
			$dql = $qb->getDQL();
			
			$query = $em->createQuery($dql);
			$placeHolders = $query->getResult();

			\Log::debug('Count of place holders found: ' . count($placeHolders));

			$placeHoldersByName = array();
			$templatePlaceHolderIds = array();

			$blocksByPlaceName = \array_combine(
					$layoutPlaceHolderNames,
					\array_fill(0, count($layoutPlaceHolderNames), array()));

			/* @var $placeHolder Entity\Abstraction\PlaceHolder */
			foreach ($placeHolders as $placeHolder) {

				$name = $placeHolder->getName();
				
				// Don't overwrite if parent place holder object was locked
				// Also locked blocks are ignored if place of parent template was locked
				if (isset($placeHoldersByName[$name])) {
					continue;
				}

				// add only in cases when it's the page place or locked one
				if ($placeHolder->getMaster() == $page || $placeHolder->getLocked()) {
					$placeHoldersByName[$name] = $placeHolder;
				} else {
					// collect not matched template place holders to search for locked blocks
					$templatePlaceHolderIds[] = $placeHolder->getId();
				}
			}

			$placeHolderIds = Entity\Abstraction\Entity::collectIds($placeHoldersByName);

			// Don't search for blocks if no place holders found
			if ( ! empty($placeHolderIds)) {
				
				$qb = $em->createQueryBuilder();
				$expr = $qb->expr();

				$condition = $expr->orX();
				// locked block condition
				if ( ! empty($templatePlaceHolderIds)) {
					$lockedBlocks = $expr->andX();
					$in = $expr->in('b.placeHolder.id', $templatePlaceHolderIds);
					$lockedBlocks->add($in);
					$lockedBlocks->add('b.locked = TRUE');
					$condition->add($lockedBlocks);
				}

				$blocks = $expr->in('b.placeHolder.id', $placeHolderIds);
				$condition->add($blocks);

				// Selection of blocks
				$qb = $em->createQueryBuilder();
				$qb->select('b')
						->from('Supra\Controller\Pages\Entity\Abstraction\Block', 'b')
						->where($condition)
						->orderBy('b.position', 'ASC');

				$dql = $qb->getDQL();
				\Log::debug("Block query : " . $dql);
				$query = $em->createQuery($dql);
				$blocks = $query->getResult();

				\Log::debug("Block count found: " . count($blocks));

				// Collect locked blocks from not locked template places
				/* @var $block Entity\TemplateBlock */
				foreach ($blocks as $block) {
					$placeHolder = $block->getPlaceHolder();
					if ($block->getLocked() && ! in_array($placeHolder, $placeHoldersByName)) {
						$blocksByPlaceName[$placeHolder->getName()][] = $block;
					}
				}

				// Collect all other blocks
				/* @var $block Entity\TemplateBlock */
				foreach ($blocks as $block) {
					$placeHolder = $block->getPlaceHolder();
					if (in_array($placeHolder, $placeHoldersByName)) {
						$blocksByPlaceName[$placeHolder->getName()][] = $block;
					}
				}

				$output = array();

				foreach ($blocksByPlaceName as $placeName => $blocks) {
					/* @var $block Entity\Abstraction\Block */
					foreach ($blocks as $block) {
						$component = $block->getComponent();
						if ( ! \class_exists($component)) {
							\Log::swarn('Block component was not found: ' . $component);
							continue;
						}
						$blockController = new $component();
						if ( ! ($blockController instanceof BlockController)) {
							\Log::swarn("Block controller $component must be instance of BlockController");
							continue;
						}

						$blockResponse = $blockController->getResponseObject($this->getRequest());

						$blockController->prepare($this->getRequest(), $blockResponse);
						$blockController->execute();
						$blockController->output();

						$blockResponsesByPlace[$placeName][] = $blockController->getResponse();

					}
				}

			}
			
		}

		$this->processLayout($layout, $blockResponsesByPlace);

	}

	/**
	 * TODO: Should move to other layout processing class maybe
	 * @param Entity\Layout $layout
	 * @param array $blocks array of block responses
	 */
	function processLayout(Entity\Layout $layout, $blocks)
	{
		$layoutContent = $layout->getFileContent();
		$response = $this->getResponse();

		$startDelimiter = '<!--placeHolder(';
		$startLength = strlen($startDelimiter);
		$endDelimiter = ')-->';
		$endLength = strlen($endDelimiter);

		do {
			$pos = strpos($layoutContent, $startDelimiter);
			if ($pos !== false) {
				$response->output(substr($layoutContent, 0, $pos));
				$layoutContent = substr($layoutContent, $pos);
				$pos = strpos($layoutContent, $endDelimiter);
				if ($pos === false) {
					break;
				}

				$placeName = substr($layoutContent, $startLength, $pos - $startLength);
				if ($placeName === '') {
					throw new Exception("Place holder name empty in layout {$layout}");
				}

				if ( ! \array_key_exists($placeName, $blocks)) {
					\Log::swarn("Place holder '$placeName' has no content");
				} else {
					/* @var $blockResponse Response\Http */
					foreach ($blocks[$placeName] as $blockResponse) {
						$blockResponse->flushToResponse($response);
					}
				}

				$layoutContent = substr($layoutContent, $pos + $endLength);
			}
		} while ($pos !== false);

		$response->output($layoutContent);
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
	public function getResponseObject(Request\RequestInterface $request)
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

}