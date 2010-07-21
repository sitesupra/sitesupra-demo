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
	 * @param RequestInterface $request
	 * @param ResponseInterface $response 
	 */
	public function execute(Request\RequestInterface $request, Response\ResponseInterface $response)
	{
		parent::execute($request, $response);
		
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
			
			/*
			$qb->select('b')
					->from('Supra\Controller\Pages\Entity\Abstraction\Block', 'b')
					->where($qb->expr()->in('b.placeHolder.name', $layoutPlaceHolderNames))
					->andWhere($qb->expr()->in('b.placeHolder.master.id', $templatePageIds))
					// templates first (type: 0-templates, 1-pages)
					->orderBy('b.placeHolder.type', 'ASC')
					->addOrderBy('b.placeHolder.master.depth', 'ASC');
			 */

			$dql = $qb->getDQL();
			
			$query = $em->createQuery($dql);
			$placeHolders = $query->getResult();

			\Log::debug('Count of place holders found: ' . count($placeHolders));

			$placeHoldersByName = array();
			$placeHolderIds = array();

			/* @var $placeHolder Entity\Abstraction\PlaceHolder */
			foreach ($placeHolders as $placeHolder) {

				$name = $placeHolder->getName();
				
				if (isset($placeHoldersByName[$name])) {
					/* @var $currentPlaceHolder Entity\Abstraction\PlaceHolder */
					$currentPlaceHolder = $placeHoldersByName[$name];
					// Don't overwrite if parent place holder object was locked
					if ($currentPlaceHolder->getLocked()) {
						continue;
					}
				}

				//FIXME: we need unlocked template PH as well to search for locked blocks!
				$placeHoldersByName[$name] = $placeHolder;
			}

			$placeHolderIds = Entity\Abstraction\Entity::collectIds($placeHoldersByName);

			// Don't search for blocks if no place holders found
			if ( ! empty($placeHolderIds)) {
				
				// Selection of blocks
				$qb = $em->createQueryBuilder();
				$qb->select('b')
						->from('Supra\Controller\Pages\Entity\Abstraction\Block', 'b')
						->where($qb->expr()->in('b.place_holder.id', $placeHolderIds));

				//TODO: continue...
				
			}
			
		}

		$response->output('So far so good');

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