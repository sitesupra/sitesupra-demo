<?php

namespace Supra\Cms\ContentManager\Sitemap;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Exception\DuplicatePagePathException;
use Supra\Cms\Exception\CmsException;
use Supra\Controller\Pages\Application\PageApplicationCollection;
use Supra\Uri\Path;
use Supra\Controller\Pages\Application\PageApplicationInterface;
use Supra\Controller\Pages\Configuration\PageApplicationConfiguration;
use Supra\Controller\Pages\PageController;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Supra\Controller\Pages\Listener\PagePathGenerator;
use Supra\Controller\Pages\Event\CmsPagePublishEventArgs;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Event\CmsPageEventArgs;

/**
 * Sitemap
 */
class SitemapAction extends PageManagerAction
{
	/**
	 * Overriden so PHP <= 5.3.2 doesn't treat sitemapAction() as a constructor
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Main method passing the sitemap tree
	 */
	public function sitemapAction()
	{
		$response = $this->loadSitemapTree(Entity\Page::CN());

		$this->getResponse()
				->setResponseData($response);
	}
	
	/**
	 * Main method passing the templates tree
	 */
	public function templatesAction()
	{
		$response = $this->loadSitemapTree(Entity\Template::CN());

		$this->getResponse()
				->setResponseData($response);
	}
	
	/**
	 * Page move action
	 */
	public function moveAction()
	{
		$this->isPostRequest();
		
		$page = null;
		
		$localization = $this->getPageLocalizationByRequestKey('page_id');
		if (is_null($localization)) {

			$page = $this->getPageByRequestKey('page_id');
			
			if (is_null($page)) {
				$pageId = $this->getRequestParameter('page_id');				
				throw new CmsException('sitemap.error.page_not_found', "Page data for page {$pageId} not found");
			}
		}

		if (is_null($page)) {
			$page = $localization->getMaster();
		}
		
		$parent = $this->getPageByRequestKey('parent_id');
		$reference = $this->getPageByRequestKey('reference_id');

		try {
			if (is_null($reference)) {
				if (is_null($parent)) {
					throw new CmsException('sitemap.error.parent_page_not_found');
				}
				$parent->addChild($page);
			} else {
				$page->moveAsPrevSiblingOf($reference);
			}
		} catch (DuplicatePagePathException $uniqueException) {
			throw new CmsException('sitemap.error.duplicate_path');
		}

//		// Move page in public as well by event (change path)
//		$publicEm = ObjectRepository::getEntityManager(PageController::SCHEMA_PUBLIC);
//		$publicPage = $publicEm->find(Entity\Page::CN(), $page->getId());
//		$eventArgs = new LifecycleEventArgs($publicPage, $publicEm);
//		$publicEm->getEventManager()->dispatchEvent(PagePathGenerator::postPageMove, $eventArgs);
//		$publicEm->flush();

		// If all went well, fire the post-publish events for published page localizations.
		$eventManager = ObjectRepository::getEventManager($this);
		
		foreach ($page->getLocalizations() as $localization) {

			$eventArgs = new CmsPagePublishEventArgs($this);
			$eventArgs->user = $this->getUser();
			$eventArgs->localization = $localization;

			$eventManager->fire(CmsPageEventArgs::postPagePublish, $eventArgs);
		}

		$this->writeAuditLog('Move', $page);
	}

	/**
	 * Lists available applications
	 */
	public function applicationsAction()
	{
		$applications = PageApplicationCollection::getInstance()
				->getApplicationConfigurationList();

		$data = array();

		foreach ($applications as $applicationConfiguration) {
			/* @var $applicationConfiguration \Supra\Controller\Pages\Configuration\PageApplicationConfiguration */

			$data[] = array(
				'id' => $applicationConfiguration->id,
				'title' => $applicationConfiguration->title,
				'icon' => $applicationConfiguration->icon,
				'new_children_first' => $applicationConfiguration->newChildrenFirst,
				'isDragable' => $applicationConfiguration->isDragable,
				'isDropTarget' => $applicationConfiguration->isDropTarget,
			);
		}

		$this->getResponse()
				->setResponseData($data);
	}

	/**
	 * Get list of pages converted to array
	 * @param string $entity
	 * @return array
	 */
	private function loadSitemapTree($entity)
	{
		$em = $this->entityManager;
		$input = $this->getRequestInput();
		$localeId = $this->getLocale()->getId();
		
		// Parent ID and level
		$parentId = null;
		$levels = null;
		
		// Calculation of parent and level
		{
			if ($input->has('parent_id')) {
				$parentId = $input->get('parent_id');
				
				// Special case for root page, need to fetch 2 levels
				if (empty($parentId)) {
					$levels = 2;
				} else {
					$levels = 1;
				}
			}

			// Is this used now?
			$parentId = $input->get('root', $parentId);
		}
		
		/* @var $parentLocalization Entity\Abstraction\Localization */
		$parentLocalization = null;
		$filter = null;
		
		if ( ! empty($parentId)) {
			list($parentId, $filter) = explode('_', $parentId);
			
			// Find localization, special case for group pages
			if ($entity == Entity\Page::CN()) {
				$parentLocalization = $em->find(Entity\PageLocalization::CN(), $parentId);

				if (is_null($parentLocalization)) {
					$rootNode = $em->find($entity, $parentId);

					if ($rootNode instanceof Entity\GroupPage) {
						$parentLocalization = $rootNode->getLocalization($localeId);
					}
				}
			} else {
				$parentLocalization = $em->find(Entity\TemplateLocalization::CN(), $parentId);
			}

			if (is_null($parentLocalization)) {
				throw new CmsException(null, 'The page the children has been requested for was not found');
			}
		}
		
		$response = $this->gatherChildrenData($entity, $parentLocalization, $filter, $levels);
		
		return $response;
	}

	/**
	 * Helper method for the main sitemap action
	 * @param Entity\Abstraction\AbstractPage $page
	 * @param string $locale
	 * @return array
	 */
	private function convertPageToArray(Entity\Abstraction\AbstractPage $page, $locale)
	{
		/* @var $data Entity\Abstraction\Localization */
		$data = null;

		// Must have group localization with ID equal with master because group localizations are not published currently
		if ($page instanceof Entity\GroupPage) {
			$data = $page->createLocalization($locale);
		} else {
			$data = $page->getLocalization($locale);
		}

		$array = array();
		$localizationExists = true;

		if (empty($data)) {

			$localeManager = ObjectRepository::getLocaleManager($this);
			$localizationExists = false;

			// try to get any localization if page is global
			if ($page->isGlobal()) {

				// TODO: temporary (and ugly also) workaround to fetch oldest localization from all available
				// this, i suppose, will be replaced with dialog window with localization selector
				$localizations = $page->getLocalizations();
				$data = $localizations->first();

				// Search for the first created localization by it's ID
				foreach ($localizations as $globalLocalization) {
					/* @var $globalLocalization Entity\Abstraction\Localization */
					if (strcmp($globalLocalization->getId(), $data->getId()) < 0) {
						$localeId = $globalLocalization->getLocale();

						if ($localeManager->exists($localeId, false)) {
							$data = $globalLocalization;
						}
					}
				}

				// collecting available localizations
				foreach ($localizations as $globalLocalization) {
					$localeId = $globalLocalization->getLocale();

					if ($localeManager->exists($localeId, false)) {
						$array['localizations'][] = $globalLocalization->getLocale();
					}
				}

				$array['global'] = true;
			} else {
				//FIXME: maybe need to throw exception here?
				return null;
			}
		}

		$nodeData = $this->loadNodeMainData($data, $localizationExists);
		if ( ! empty($nodeData)) {
			$array = array_merge($nodeData, $array);
		}
		
		return $array;
	}

	/**
	 * Returns children page array data
	 * @param string $entity
	 * @param ApplicationLocalization $parentLocalization
	 * @param string $filter
	 * @param integer $levels
	 * @param boolean $count
	 * @return mixed
	 */
	private function gatherChildrenData($entity, $parentLocalization, $filter = null, $levels = null, $count = false)
	{
		$em = $this->entityManager;
		$input = $this->getRequestInput();
		$localeId = $this->getLocale()->getId();
		
		$existingOnly = (boolean) $input->getValidIfExists('existing_only', 'boolean');
		
		/* @vdoc $pageFinder \Supra\Controller\Pages\Finder\PageFinder */
		$pageFinder = null;
		
		if ($entity == Entity\Page::CN()) {
			$pageFinder = new \Supra\Controller\Pages\Finder\PageFinder($em);
		} else {
			$pageFinder = new \Supra\Controller\Pages\Finder\TemplateFinder($em);
		}
		
		// Reading by one level because need specific reading strategy for application pages
		if (empty($parentLocalization)) {
			$pageFinder->addLevelFilter(0, 0);
		} else {
			$rootNode = $parentLocalization->getMaster();
			$pageFinder->addFilterByParent($rootNode, 1, 1);
		}
		
		$queryBuilder = $pageFinder->getQueryBuilder();
		
		if ($existingOnly) {
			$queryBuilder->leftJoin('e.localizations', 'l_', 'WITH', 'l_.locale = :locale')
					->setParameter('locale', $localeId)
					->leftJoin('e.localizations', 'l')
					->andWhere('l_.id IS NOT NULL OR e INSTANCE OF ' . Entity\GroupPage::CN());
		} else {
			$queryBuilder->leftJoin('e.localizations', 'l_', 'WITH', 'l_.locale = :locale')
					->setParameter('locale', $localeId)
					->leftJoin('e.localizations', 'l')
					->andWhere('l_.id IS NOT NULL OR e.global = true');
		}
		
		$filterFolders = array();
		$application = null;
		
		if ($parentLocalization instanceof Entity\ApplicationLocalization) {
			
			$application = PageApplicationCollection::getInstance()
					->createApplication($parentLocalization, $em);
			
			// TODO: remove the type cast when methods are moved to the abstraction
			/* @var $application \Supra\Controller\Pages\News\NewsApplication */
			$filterFolders = (array) $application->getFilterFolders($filter);
			$application->applyFilters($queryBuilder, $filter);
		}
		
		$offset = $input->getValidIfExists('offset', 'smallint');
		$limit = $input->getValidIfExists('resultsPerRequest', 'smallint');
		
		$query = $queryBuilder->getQuery();
		$query->setFirstResult($offset);
		$query->setMaxResults($limit);
		
		$paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query, true);
		
		// TODO: fix, shouldn't return mixed result depending on arguments
		if ($count) {
			// When only the count is needed...
			$count = count($filterFolders) + count($paginator);
			
			return $count;
		} else {
			
			$response = array();
			
			$pages = iterator_to_array($paginator);

			// Prepend the filter folders
			$pages = array_merge($filterFolders, $pages);
			
			if ( ! is_null($levels)) {
				$levels--;
			}
			
			foreach ($filterFolders as $filterFolder) {
				if ( ! $filterFolder instanceof Entity\TemporaryGroupPage) {
					throw new \LogicException("Application " . get_class($application) . ' returned invalid filter folders');
				}
			}
			
			foreach ($pages as $page) {
				/* @var $page Entity\Abstraction\AbstractPage */
				
				$pageData = $this->convertPageToArray($page, $localeId);
				
				// Skipping the page
				if (is_null($pageData)) {
					continue;
				}
				
				$pageData['children_count'] = 0;
				
				$localization = $page->getLocalization($localeId);
				
				if ( ! empty($localization)) {
					$filter = null;
					if ($page instanceof Entity\TemporaryGroupPage) {
						$filter = $page->getTitle();
						$localization = $parentLocalization;
						
						// TODO: for now it's enabled for all filter folders
						$pageData['childrenListStyle'] = 'scrollList';
						$pageData['selectable'] = false;
						$pageData['editable'] = false;
						$pageData['isDragable'] = false;
						$pageData['isDropTarget'] = true;
						$pageData['new_children_first'] = true;
					}
					
					if ($levels === 0) {
						$pageData['children_count'] = $this->gatherChildrenData($entity, $localization, $filter, $levels, true);
					} else {
						$pageData['children'] = $this->gatherChildrenData($entity, $localization, $filter, $levels, false);
						$pageData['children_count'] = count($pageData['children']);
					}
					
					// TODO: might be job for JS
					if ($pageData['children_count'] > 0 && $pageData['icon'] = 'page') {
						$pageData['icon'] = 'folder';
					}
				}
				
				$response[] = $pageData;
			}
			
			return $response;
		}
	}
	
}
