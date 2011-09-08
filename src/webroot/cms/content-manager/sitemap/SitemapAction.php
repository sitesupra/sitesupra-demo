<?php

namespace Supra\Cms\ContentManager\Sitemap;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Exception\DuplicatePagePathException;
use Supra\Cms\Exception\CmsException;

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
		$pages = array();
		$locale = $this->getLocale();
		
		$em = $this->entityManager;
		
		$entities = array(
			PageRequest::PAGE_ENTITY,
//			PageRequest::TEMPLATE_ENTITY
		);
		
		$response = array();
		
		foreach ($entities as $entity) {
		
			$pageRepository = $em->getRepository($entity);
			/* @var $pageRepository \Supra\Controller\Pages\Repository\PageRepository */

			$rootNodes = $pageRepository->getRootNodes();

			foreach ($rootNodes as $rootNode) {
				$tree = $this->buildTreeArray($rootNode, $locale);
				// TODO: hardcoded
				$tree['icon'] = 'home';
				
				$response[] = $tree;
			}
		}
		
		$this->getResponse()
				->setResponseData($response);
	}
	
	/**
	 * Called when save is performed inside the sitemap
	 */
	public function saveAction()
	{
		$this->isPostRequest();
		$pageData = $this->getPageData();
		
		if ($this->hasRequestParameter('title')) {
			$title = $this->getRequestParameter('title');
			$pageData->setTitle($title);
		}
		
		if ($this->hasRequestParameter('path')) {
			if ($pageData instanceof Entity\PageData) {
				$pathPart = $this->getRequestParameter('path');
				
				try {
					$pageData->setPathPart($pathPart);
				} catch (DuplicatePagePathException $uniqueException) {
					$this->getResponse()
							->setErrorMessage("{#sitemap.error.duplicate_path#}");
					
					// Clear the unit of work
					$this->entityManager->clear();
				}
			}
		}
		
		$this->entityManager->flush();
		$this->outputPage($pageData);
	}
	
	/**
	 * Page move action
	 */
	public function moveAction()
	{
		$this->isPostRequest();
		
		$pageId = $this->getRequestParameter('page_id');
		$parentId = $this->getRequestParameter('parent_id');
		$referenceId = $this->getRequestParameter('reference_id');
		
		/* @var $page Entity\Abstraction\Page */
		$page = $this->entityManager->find(PageRequest::PAGE_ABSTRACT_ENTITY, $pageId);
		/* @var $parent Entity\Abstraction\Page */
		$parent = $this->entityManager->find(PageRequest::PAGE_ABSTRACT_ENTITY, $parentId);
		/* @var $reference Entity\Abstraction\Page */
		$reference = $this->entityManager->find(PageRequest::PAGE_ABSTRACT_ENTITY, $referenceId);
		
		if (is_null($page)) {
			throw new CmsException('sitemap.error.page_not_found');
		}
		
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
	 * @param Entity\Abstraction\Page $page
	 * @param string $locale
	 * @return array
	 */
	private function buildTreeArray(Entity\Abstraction\Page $page, $locale)
	{
		$data = $page->getData($locale);
		
		if (empty($data)) {
			return null;
		}
		
		$pathPart = null;
		$templateId = null;
		
		//TODO: need to know template ID as well
		if ($page instanceof Entity\Page) {
			$templateId = $data->getTemplate()
					->getId();
		}
		
		if ($data instanceof Entity\PageData) {
			$pathPart = $data->getPathPart();
		}
		
		$array = array(
			'id' => $page->getId(),
			'title' => $data->getTitle(),
			'template' => $templateId,
			'path' => $pathPart,
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
}
