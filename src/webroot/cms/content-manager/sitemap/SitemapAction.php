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
		$input = $this->getRequestInput();
		$parentId = null;
		
		if ( ! $input->isEmpty('parent_id', false)) {
			$parentId = $input->get('parent_id');
		}
		
		$response = $this->loadSitemapTree(Entity\Page::CN(), $parentId, 1);

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
	 * Helper method for the main sitemap action
	 * @param Entity\Abstraction\AbstractPage $page
	 * @param string $locale
	 * @param boolean $skipRoot
	 * @param boolean $skipGlobal
	 * @param array $inheritConfig
	 * @param integer $levels
	 * @return array
	 */
	private function buildTreeArray(Entity\Abstraction\AbstractPage $page, $locale, $skipRoot = false, $skipGlobal = false, $inheritConfig = array(), $levels = null)
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

		$array = array();
		$localizationExists = true;

		if (empty($data)) {

			$localeManager = ObjectRepository::getLocaleManager($this);
			$localizationExists = false;

			// try to get any localization if page is global
			if ($page->isGlobal()) {

				if ($skipGlobal) {
					return null;
				}

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

				$isGlobal = true;
			} else {

				return null;
			}
		}

		if ( ! $skipRoot) {
			$nodeData = $this->loadNodeMainData($data, $localizationExists);
			if ( ! empty($nodeData)) {
				$array = array_merge($nodeData, $array);
			}
		}

		$children = null;

		if ( ! $isGlobal && (is_null($levels) || $levels >= 0)) {
			if ($page instanceof Entity\ApplicationPage) {
				$application = PageApplicationCollection::getInstance()
						->createApplication($data, $this->entityManager);

				$application->showInactivePages(true);

//				$modes = $application->getAvailableSitemapViewModes();
//
//				$collapsedMode = in_array(PageApplicationInterface::SITEMAP_VIEW_COLLAPSED, $modes);
//				$expandedMode = in_array(PageApplicationInterface::SITEMAP_VIEW_EXPANDED, $modes);
//
//				$forceExpand = $this->hasRequestParameter('expand');
//				$hasRootId = $this->hasRequestParameter('root');
//				$showHidden = ($hasRootId && ! $forceExpand);
//
//				if ($showHidden) {
//					$children = $application->getHiddenPages();
//				} elseif ($forceExpand && $expandedMode) {
//					$children = $application->expandedSitemapView();
//				} elseif ($collapsedMode) {
//					//TODO: children could be a grouped array
//					$children = $application->collapsedSitemapView();
//
//					// Send sitemap that expanded view is available for the root node
//					if ($expandedMode) {
//						$array['collapsed'] = true;
//					}
//				} elseif ($expandedMode) {
//					$children = $application->expandedSitemapView();
//				} else {
//					$children = array();
//				}

//				$array['has_hidden_pages'] = $application->hasHiddenPages();
//				$array['childrenListStyle'] = 'scrollList';
				
//				$inheritConfig = $application->getInheritConfig();

				if ($application instanceof \Supra\Controller\Pages\News\NewsApplication) {
					$children = $application->getSitemap();
					$array['isDropTarget'] = true;
					$inheritConfig['isDropTarget'] = true;
					
					$this->applications[$data->getId()] = $application;
				}
			} else {
				$children = $page->getChildren();
			}

			$childrenArray = array();
			
			if (is_array($children)) {
				$childrenArray = $this->convertPagesToArray($children, $locale, $skipGlobal, $inheritConfig, $levels);
			}

			if ( ! $skipRoot) {
				$array['children_count'] = 0;
				
				if (count($childrenArray) > 0) {
					
					$array['children_count'] = count($childrenArray);
					
					if (is_null($levels) || $levels > 0) {
						$array['children'] = $childrenArray;
					}

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
	 * @param boolean $skipGlobal
	 * @param array $config
	 * @param integer $levels
	 * @return array
	 */
	private function convertPagesToArray(array $children, $locale, $skipGlobal = false, $config = array(), $levels = null)
	{
		$childrenArray = array();
		if ( ! is_null($levels)) {
			$levels--;
		}

		foreach ($children as $name => $child) {

			// TODO: remove
			if (is_array($child)) {
				$group = new Entity\TemporaryGroupPage();
				$group->setTitle($name);
				$group->setChildren($child);

				// Ignore the levels if array is received
				$childArray = $this->buildTreeArray($group, $locale, false, $skipGlobal, $config, null);
				$childArray['isDragable'] = false;
				$childArray['childrenListStyle'] = 'scrollList';
			// TODO: remove
			} elseif (is_integer($child)) {
				$group = new Entity\TemporaryGroupPage();
				$group->setTitle($name);
				$group->setNumberChildren($child);
				
				// Ignore the levels if array is received
				$childArray = $this->buildTreeArray($group, $locale, false, $skipGlobal, $config, null);
				$childArray['isDragable'] = false;
				$childArray['childrenListStyle'] = 'scrollList';
				$childArray['children_count'] = $child;
			} elseif ($child instanceof Entity\TemporaryGroupPage) {
				$childArray = $this->buildTreeArray($child, $locale, false, $skipGlobal, $config, null);
				
				//TODO: move to "buildTreeArray" and Entity\TemporaryGroupPage properties
				$childArray['isDragable'] = true;
				$childArray['isDropTarget'] = true;
				$childArray['childrenListStyle'] = 'scrollList';
				$childArray['children_count'] = $child->getNumberChildren();
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

				$childArray = $this->buildTreeArray($child, $locale, false, $skipGlobal, $config, $levels);
			}

			// it is possibly, that childrens should inherit some config values from parent node
			if (( ! empty($childArray) && is_array($childArray))
					&& ( ! empty($config) && is_array($config))) {
				$childArray = array_merge($childArray, $config);
			}

			if ( ! empty($childArray)) {
				$childrenArray[] = $childArray;
			}
		}

		return $childrenArray;
	}

	/**
	 * Returns Template or Page data
	 * @param string $entity
	 * @return array
	 */
	protected function loadSitemapTree($entity, $parentId = null, $levels = null)
	{
		$localeId = $this->getLocale()->getId();

		$existingOnly = false;
		$input = $this->getRequestInput();

		if ($input->has('existing_only')) {
			$existingOnly = $input->getValid('existing_only', 'boolean', false);
		}

		$em = $this->entityManager;

		$response = array();

		$pageRepository = $em->getRepository($entity);
		/* @var $pageRepository \Supra\Controller\Pages\Repository\PageRepository */

		$rootNodes = array();
		$skipRoot = false;
		
		// Is this used now?
		if ($this->hasRequestParameter('root')) {
			$parentId = $this->getRequestParameter('root');
		}
		
		if ( ! empty($parentId)) {
			
			$temporaryGroupId = null;
			list($parentId, $temporaryGroupId) = explode('_', $parentId);
			
			$rootNodeLocalization = null;
			
			if ($entity == Entity\Page::CN()) {
				$rootNodeLocalization = $em->find(Entity\PageLocalization::CN(), $parentId);
			} else {
				$rootNodeLocalization = $em->find(Entity\TemplateLocalization::CN(), $parentId);
			}

			/* @var $rootNodeLocalization Entity\PageLocalization */

			if (is_null($rootNodeLocalization)) {
				
				$rootNode = $em->find($entity, $parentId);
				
				if ($rootNode instanceof Entity\GroupPage) {
					$rootNodeLocalization = $rootNode->getLocalization($localeId);
				}
			}
			
			if (is_null($rootNodeLocalization)) {
				throw new CmsException(null, 'The parent page not found');
			}

			$rootNode = $rootNodeLocalization->getMaster();
			$skipRoot = true;

			if ( ! is_null($temporaryGroupId) && ! is_null($levels)) {
				$levels++;
			}
			
			$response = $this->buildTreeArray($rootNode, $localeId, true, $existingOnly, null, $levels);
			
			if ( ! is_null($temporaryGroupId)) {
				
				$found = false;
				
				if (isset($this->applications[$rootNodeLocalization->getId()])) {
					
					$application = $this->applications[$rootNodeLocalization->getId()];
					
					// FIXME: just a temporary implementation of news application list...
					if ($application instanceof \Supra\Controller\Pages\News\NewsApplication) {
						$response = array();
						
						$resultsPerRequest = null;
						if ($input->has('resultsPerRequest')) {
							$resultsPerRequest = $input->getValid('resultsPerRequest', 'integer');
						}
						
						$offset = null;
						if ($input->has('offset')) {
							$offset = $input->getValid('offset', 'integer');
						}
						
						$news = $application->getList($resultsPerRequest, $offset);
						
						foreach ($news as $newsItem) {
							$response[] = $this->buildTreeArray($newsItem, $localeId, false, $existingOnly, null, $levels);
						}
						
						$found = true;
					}
					
				}
				
				if ( ! $found) {
					throw new CmsException(null, "The application subgroup not found");
				}
			}
		} else {
			$rootNodes = $pageRepository->getRootNodes();

			foreach ($rootNodes as $rootNode) {
				$tree = $this->buildTreeArray($rootNode, $localeId, $skipRoot, $existingOnly, null, $levels);
				if ( ! is_null($tree)) {
					$response[] = $tree;
				}
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