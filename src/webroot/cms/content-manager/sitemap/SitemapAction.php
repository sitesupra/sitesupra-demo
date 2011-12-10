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
	}

	/**
	 * Helper method for the main sitemap action
	 * @param Entity\Abstraction\AbstractPage $page
	 * @param string $locale
	 * @return array
	 */
	private function buildTreeArray(Entity\Abstraction\AbstractPage $page, $locale, $skipRoot = false)
	{
		$data = $page->getLocalization($locale);	
		
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
		
		if ($isGlobal) {
			$array['global'] = true;
		}

		return $array;
	}
	
	/**
	 * @param array $children
	 * @param strng $locale
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
	
}
