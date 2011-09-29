<?php

namespace Supra\Cms\ContentManager\Sitemap;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Exception\DuplicatePagePathException;
use Supra\Cms\Exception\CmsException;
use Supra\Controller\Pages\Application\PageApplicationCollection;

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
		$response = $this->loadSitemapTree(PageRequest::PAGE_ENTITY);

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
	private function buildTreeArray(Entity\Abstraction\AbstractPage $page, $locale)
	{
		$data = $page->getLocalization($locale);

		if (empty($data)) {
			return null;
		}

		$pathPart = null;
		$templateId = null;
		$basePath = '';

		//TODO: need to know template ID as well
		if ($page instanceof Entity\Page) {
			$templateId = $data->getTemplate()
					->getId();
		}

		if ($data instanceof Entity\PageLocalization) {
			$pathPart = $data->getPathPart();
			
			if ( ! $page->isRoot()) {
				$parentPage = $page->getParent();
				$parentLocalization = $parentPage->getLocalization($locale);
				
				if ( ! $parentLocalization instanceof Entity\PageLocalization) {
					throw new CmsException(null, "Parent page has no localization in the selected language");
				}
				
				$basePath = $parentLocalization->getPath();
				
				if ($parentPage instanceof Entity\ApplicationPage) {
					$applicationId = $parentPage->getApplicationId();
					$application = PageApplicationCollection::getInstance()
							->createApplication($applicationId);
					
					if (empty($application)) {
						throw new CmsException(null, "Application '$applicationId' was not found");
					}
					
					$pageBasePath = $application->generatePath($data);
					$basePath = \Supra\Uri\Path::concat($basePath, $pageBasePath);
				}
			}
		}
		
		$array = array(
			'id' => $data->getId(),
			'title' => $data->getTitle(),
			'template' => $templateId,
			'path' => $pathPart,
			'basePath' => $basePath,
			
			// TODO: hardcoded
			'icon' => 'page',
			'preview' => '/cms/lib/supra/img/sitemap/preview/page-1.jpg'
		);

		$array['children'] = array();

		foreach ($page->getChildren() as $child) {
			$childArray = $this->buildTreeArray($child, $locale);

			if ( ! empty($childArray)) {
				$array['children'][] = $childArray;
			}
		}

		if (count($array['children']) == 0) {
			unset($array['children']);
		} else {
			// TODO: hardcoded
			$array['icon'] = 'folder';
		}

		return $array;
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
		$rootNodes = $pageRepository->getRootNodes();

		foreach ($rootNodes as $rootNode) {
			$tree = $this->buildTreeArray($rootNode, $localeId);
			// TODO: hardcoded
			$tree['icon'] = 'home';

			$response[] = $tree;
		}
		
		return $response;
	}
}
