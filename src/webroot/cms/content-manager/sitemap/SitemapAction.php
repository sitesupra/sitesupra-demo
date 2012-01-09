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
use Supra\Cms\CmsController;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Sitemap
 */
class SitemapAction extends PageManagerAction
{

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
		$response = $this->loadSitemapTree(PageRequest::TEMPLATE_ENTITY);

		$this->getResponse()
				->setResponseData($response);
	}

	/**
	 * Page move action
	 */
	public function moveAction()
	{
		$this->isPostRequest();

		$page = $this->getPageLocalization()->getMaster();
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
		
		// Move page in public as well by event (change path)
		$publicEm = ObjectRepository::getEntityManager(PageController::SCHEMA_PUBLIC);
		$publicPage = $publicEm->find(Entity\Page::CN(), $page->getId());
		$eventArgs = new LifecycleEventArgs($publicPage, $publicEm);
		$publicEm->getEventManager()->dispatchEvent(PagePathGenerator::postPageMove, $eventArgs);
		$publicEm->flush();
		
		// If all went well, fire the post-publish event for published page localization.
		$eventArgs = new CmsPagePublishEventArgs();
		$eventArgs->user = $this->getUser();
		$eventArgs->localization = $this->getPageLocalization();

		$eventManager = ObjectRepository::getEventManager($this);
		$eventManager->fire(CmsController::EVENT_POST_PAGE_PUBLISH, $eventArgs);
			
		$this->writeAuditLog('Move', $page);
	}

	/**
	 * Helper method for the main sitemap action
	 * @param Entity\Abstraction\AbstractPage $page
	 * @param string $locale
	 * @return array
	 */
	private function buildTreeArray(Entity\Abstraction\AbstractPage $page, $locale, $skipRoot = false)
	{
		/* @var $data Entity\Abstraction\Localization */
		$data = null;
		$isGlobal = false;
		
		// Must have group localization with ID equal with master because group localizations are not published currently
		if ($page instanceof Entity\GroupPage) {
			$data = $page->createLocalization($locale);
		} else {
			$data = $page->getLocalization($locale);
		}
		
		if (empty($data)) {
			// try to get any localization if page is global
			if ($page->isGlobal()) {
				// hoping that there is at least one page data instance (naive)
				$data = $page->getLocalizations()->first();
				$isGlobal = true;
			} else {

				return null;
			}
		}
		
		$array = array();

		if ( ! $skipRoot) {
			$array = $this->loadNodeMainData($data);
		}

		$children = null;
		
		if ( ! $isGlobal) {
			if ($page instanceof Entity\ApplicationPage) {
				$application = PageApplicationCollection::getInstance()
						->createApplication($data, $this->entityManager);

				$application->showInactivePages(true);

				$modes = $application->getAvailableSitemapViewModes();

				$collapsedMode = in_array(PageApplicationInterface::SITEMAP_VIEW_COLLAPSED, $modes);
				$expandedMode = in_array(PageApplicationInterface::SITEMAP_VIEW_EXPANDED, $modes);

				$forceExpand = $this->hasRequestParameter('expand');
				$hasRootId = $this->hasRequestParameter('root');
				$showHidden = ($hasRootId && ! $forceExpand);

				if ($showHidden) {
					$children = $application->getHiddenPages();
				} elseif ($forceExpand && $expandedMode) {
					$children = $application->expandedSitemapView();
				} elseif ($collapsedMode) {
					//TODO: children could be a grouped array
					$children = $application->collapsedSitemapView();

					// Send sitemap that expanded view is available for the root node
					if ($expandedMode) {
						$array['collapsed'] = true;
					}
				} elseif ($expandedMode) {
					$children = $application->expandedSitemapView();
				} else {
					$children = array();
				}

				$array['has_hidden_pages'] = $application->hasHiddenPages();

				//TODO: pass to client if there are any hidden pages

			} elseif ($page instanceof Entity\TemporaryGroupPage) {
				$children = $page->getChildren();
			} else {
				$children = $page->getChildren();
			}

			$childrenArray = $this->convertPagesToArray($children, $locale);

			if ( ! $skipRoot) {
				if (count($childrenArray) > 0) {
					$array['children'] = $childrenArray;

					// TODO: hardcoded
					if ($array['icon'] == 'page') {
						$array['icon'] = 'folder';
					}
				}
			} else {
				$array = $childrenArray;
			}
		}
		
		if ($isGlobal) {
			$array['global'] = true;
		}

		return $array;
	}
	
	/**
	 * @param array $children
	 * @param string $locale
	 * @return array
	 */
	private function convertPagesToArray(array $children, $locale)
	{
		$childrenArray = array();
		
		foreach ($children as $name => $child) {
			
			if (is_array($child)) {
				$group = new Entity\TemporaryGroupPage();
				$group->setTitle($name);
				$group->setChildren($child);
				
				$groupArray = $this->buildTreeArray($group, $locale);
				
				$childrenArray[] = $groupArray;
			} else {
			
				// Application responds with localization objects..
				//FIXME: fix inconsistency
				if ($child instanceof Entity\Abstraction\Localization) {
					$child = $child->getMaster();
				}
				
				if ( ! $child instanceof Entity\Abstraction\AbstractPage) {
					$this->log->error("Wrong instance of page node received, array: ", $children);
					
					continue;
				}

				$childArray = $this->buildTreeArray($child, $locale);

				if ( ! empty($childArray)) {
					$childrenArray[] = $childArray;
				}
			}
		}
		
		return $childrenArray;
	}
	
	/**
	 * Returns Template or Page data
	 * @param string $entity
	 * @return array
	 */
	protected function loadSitemapTree($entity)
	{
		$pages = array();
		$localeId = $this->getLocale()->getId();

		$em = $this->entityManager;

		$response = array();

		$pageRepository = $em->getRepository($entity);
		/* @var $pageRepository \Supra\Controller\Pages\Repository\PageRepository */
		
		$rootNodes = array();
		$skipRoot = false;
		
		if ($this->hasRequestParameter('root')) {
			$rootId = $this->getRequestParameter('root');
			$rootNodeLocalization = $em->find(Entity\PageLocalization::CN(), $rootId);
			
			/* @var $rootNodeLocalization Entity\PageLocalization */
			
			if (is_null($rootNodeLocalization)) {
				$this->log->warn("Root node $rootId not found in sitemap action");
				
				return array();
			}
			
			$rootNode = $rootNodeLocalization->getMaster();
			$skipRoot = true;
			
			$response = $this->buildTreeArray($rootNode, $localeId, true);
			
		} else {
			$rootNodes = $pageRepository->getRootNodes();
			
			foreach ($rootNodes as $rootNode) {
				$tree = $this->buildTreeArray($rootNode, $localeId, $skipRoot);
				$response[] = $tree;
			}
		}

		return $response;
	}
	
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
	
}
