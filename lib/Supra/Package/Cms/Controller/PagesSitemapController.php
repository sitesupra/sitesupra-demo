<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Entity\Page;
use Supra\Package\Cms\Entity\Template;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\GroupPage;
use Supra\Package\Cms\Entity\TemplateLocalization;
use Supra\Package\Cms\Entity\TemporaryGroupPage;
use Supra\Package\Cms\Pages\Finder\PageFinder;
use Supra\Package\Cms\Pages\Finder\TemplateFinder;
use Supra\Package\Cms\Pages\Exception\DuplicatePagePathException;
use Supra\Package\Cms\Exception\CmsException;

class PagesSitemapController extends AbstractPagesController
{
	/**
	 * Responds with Pages sitemap tree data
	 *
	 * @return SupraJsonResponse
	 */
	public function pagesListAction()
	{
		return new SupraJsonResponse(
				$this->loadSitemapTree(Page::CN())
		);
	}

	/**
	 * Responds with Templates sitemap tree data
	 *
	 * @return SupraJsonResponse
	 */
	public function templatesListAction()
	{
		return new SupraJsonResponse(
				$this->loadSitemapTree(Template::CN())
		);
	}

	/**
	 * Responds with Page Apps configuration
	 * 
	 * @return SupraJsonResponse
	 */
	public function applicationsListAction()
	{
		$manager = $this->getPageApplicationManager();

		foreach ($manager->getAllApplications() as $application) {
			$responseData[] = array(
				'id'		=> $application->getId(),
				'title'		=> $application->getTitle(),
				'icon'		=> $application->getIcon(),
				'isDropTarget' => $application->getAllowChildren(),
				'childInsertPolicy' => $application->getNewChildInsertPolicy(),
			);
		}

		return new SupraJsonResponse($responseData);
	}

	/**
	 * Handles Page move request.
	 */
	public function moveAction()
	{
		$this->isPostRequest();
		
		$localization = $this->getPageLocalization();

		$page = $localization->getMaster();
		
		$input = $this->getRequestInput();

		$this->lockNestedSet($page);

		try {
			if ($input->has('reference_id')) {
				$sibling = $this->getPageByRequestKey('reference_id');

				if ($sibling === null) {
					throw new CmsException(null, sprintf('Sibling page [%s] not found.', $input->get('reference_id')));
				}

				if ($input->get('reference_type', 'before') === 'after') {
					$page->moveAsNextSiblingOf($sibling);
				} else {
					$page->moveAsPrevSiblingOf($sibling);
				}
				
			} elseif ($input->has('parent_id')) {

				$parent = $this->getPageByRequestKey('parent_id');

				if ($parent === null) {
					throw new CmsException(null, sprintf('Parent page [%s] not found.', $input->get('parent_id')));
				}

				$parent->addChild($page);
			} else {
				throw new \RuntimeException('Move destination is unknown.');
			}

		} catch (DuplicatePagePathException $e) {
			throw new CmsException('sitemap.error.duplicate_path');
		}

		$this->unlockNestedSet($page);

// @FIXME: it is not correct to fire publish event while only move happened.
// need to check why this was here and do it right.
		// If all went well, fire the post-publish events for published page localizations.
//		$eventManager = ObjectRepository::getEventManager($this);
//
//		foreach ($page->getLocalizations() as $localization) {
//
//			$eventArgs = new Event\PageCmsEventArgs();
//
//			$eventArgs->user = $this->getUser();
//			$eventArgs->localization = $localization;
//
//			$eventManager->fire(Event\PageCmsEvents::pagePostPublish, $eventArgs);
//		}

		return new SupraJsonResponse();
	}
	
	/**
	 * Get list of pages converted to array
	 * @param string $entity
	 * @return array
	 */
	private function loadSitemapTree($entity)
	{
		$em = $this->getEntityManager();
		
		$input = $this->getRequestInput();
		$localeId = $this->getCurrentLocale()->getId();

		// Parent ID and level
		$levels = null;
		$parentId = null;
		
		if ($input->has('parent_id')) {
			$parentId = $input->get('parent_id');

			// Special case for root page, need to fetch 2 levels
			if (empty($parentId)) {
				$levels = 2;
			} else {
				$levels = 1;
			}
		} else {
			$levels = 2;
		}

		$parentLocalization = null;
		$filter = null;

		if ( ! empty($parentId)) {

			if (strpos($parentId, '_') !== false) {
				list($parentId, $filter) = explode('_', $parentId);
			}

			// Find localization, special case for group pages
			if ($entity == Page::CN()) {
				$parentLocalization = $em->find(PageLocalization::CN(), $parentId);

				if (is_null($parentLocalization)) {
					$rootNode = $em->find($entity, $parentId);

					if ($rootNode instanceof GroupPage) {
						$parentLocalization = $rootNode->getLocalization($localeId);
					}
				}
			} else {
				$parentLocalization = $em->find(TemplateLocalization::CN(), $parentId);
			}

			if (is_null($parentLocalization)) {
				throw new CmsException(null, 'The page the children has been requested for was not found');
			}
		}

		$response = $this->gatherChildrenData($entity, $parentLocalization, $filter, $levels);

		return $response;
	}

	/**
	 * Returns children page array data
	 * @param string $entity
	 * @param Localization $parentLocalization
	 * @param string $filter
	 * @param integer $levels
	 * @param boolean $count
	 * @return mixed
	 */
	private function gatherChildrenData($entity, $parentLocalization, $filter = null, $levels = null, $count = false)
	{
		$input = $this->getRequestInput();
		$localeId = $this->getCurrentLocale()->getId();
		
		$existingOnly = $input->filter('existing_only', false, false, FILTER_VALIDATE_BOOLEAN);

		$customTitleQuery = $input->get('query', false);

		$entityManager = $this->getEntityManager();
		$pageFinder = $entity === Page::CN() 
				? new PageFinder($entityManager)
				: new TemplateFinder($entityManager);

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
					->andWhere('l_.id IS NOT NULL OR e INSTANCE OF ' . GroupPage::CN());
		} else {
			$queryBuilder->leftJoin('e.localizations', 'l_', 'WITH', 'l_.locale = :locale')
					->setParameter('locale', $localeId)
					->leftJoin('e.localizations', 'l')
					->andWhere('l_.id IS NOT NULL OR e.global = true OR (e.level = 0 AND e.global = false)');
		}

		if($customTitleQuery) {
			$queryBuilder->andWhere('l_.title LIKE :customTitleQuery');
			$queryBuilder->setParameter('customTitleQuery', $customTitleQuery . '%');
		}

		$filterFolders = array();
		$application = null;

		if ($parentLocalization instanceof ApplicationLocalization) {

			// This is bad solution for detecting where the sitemap has been requested from.
			// Should group by month if sitemap requested in the sidebar.
			//FIXME: JS should pass preference maybe
			if (empty($filter) && $existingOnly) {
				$filter = 'group';
			}

			$application = $this->getPageApplicationManager()
					->createApplicationFor($parentLocalization, $this->getEntityManager());

			$filterFolders = $application->getFilterFolders($queryBuilder, $filter);
			
			$application->applyFilters($queryBuilder, $filter);
		}

		$query = $queryBuilder->getQuery();

		if ($input->has('offset')) {
			$offset = $input->getInt('offset');
			$query->setFirstResult($offset);
		}

		if ($input->has('resultsPerRequest')) {
			$limit = $input->getInt('resultsPerRequest');
			$query->setMaxResults($limit);
		}

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
				$levels --;
			}

			foreach ($filterFolders as $filterFolder) {
				if ( ! $filterFolder instanceof TemporaryGroupPage) {
					throw new \LogicException("Application " . get_class($application) . ' returned invalid filter folders');
				}
			}

			foreach ($pages as $page) {

				$pageData = $this->convertPageToArray($page, $localeId);

				// Skipping the page
				if (is_null($pageData)) {
					continue;
				}

				$pageData['children_count'] = 0;

				$localization = $page->getLocalization($localeId);

				if ( ! empty($localization)) {
					$filter = null;
					if ($page instanceof TemporaryGroupPage) {
						$filter = $page->getTitle();
						$localization = $parentLocalization;

						// TODO: for now it's enabled for all filter folders
						$pageData['childrenListStyle'] = 'scrollList';
						$pageData['selectable'] = false;
						$pageData['editable'] = false;
						$pageData['isDraggable'] = false;
						$pageData['isDropTarget'] = true;
						$pageData['new_children_first'] = true;
					}

					if ($levels === 0 && $page instanceof TemporaryGroupPage && $page->hasCalculatedNumberChildren()) {
						$pageData['children_count'] = $page->getNumberChildren();
					} elseif ($levels === 0) {
						$pageData['children_count'] = $this->gatherChildrenData($entity, $localization, $filter, $levels, true);
					} else {
						$pageData['children'] = $this->gatherChildrenData($entity, $localization, $filter, $levels, false);
						$pageData['children_count'] = count($pageData['children']);
					}

//					// TODO: might be job for JS
//					if ($pageData['children_count'] > 0
//							&& ! empty($pageData['icon'])
//							&& $pageData['icon'] === 'page') {
//
//						$pageData['icon'] = 'folder';
//					}
				}

				$response[] = $pageData;
			}

			return $response;
		}
	}
}