<?php

namespace Supra\Cms\ContentManager\sitemap;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;

/**
 * Sitemap
 */
class SitemapAction extends PageManagerAction
{
	public function sitemapAction()
	{
		$pages = array();
		$locale = $_GET['locale'];
		
		//TODO: hardcoded
		$locale = 'en';
		
		$em = \Supra\Database\Doctrine::getInstance()
				->getEntityManager();
		
		$entities = array(
			PageRequest::PAGE_ENTITY,
			PageRequest::TEMPLATE_ENTITY
		);
		
		$response = array();
		
		foreach ($entities as $entity) {
		
			$pageRepository = $em->getRepository($entity);
			/* @var $pageRepository \Supra\Controller\Pages\Repository\PageRepository */

			$rootNodes = $pageRepository->getRootNodes();

			foreach ($rootNodes as $rootNode) {
				$tree = $this->buildTreeArray($rootNode, $locale);
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
				$pageData->setPathPart($pathPart);
			}
		}
		
		$this->entityManager->flush();
	}
	
	/**
	 * @param Entity\Abstraction\Page $page
	 * @param string $locale
	 * @return array
	 */
	private function buildTreeArray(Entity\Abstraction\Page $page, $locale)
	{
		$data = $page->getData($locale);
		$pathPart = null;
		$templateName = null;
		
		//TODO: need to know template ID as well
		if ($page instanceof Entity\Page) {
			$templateName = $page->getTemplate()
					->getData($locale)
					->getTitle();
		}
		
		if ($data instanceof Entity\PageData) {
			$pathPart = $data->getPathPart();
		}
		
		$array = array(
			'id' => $page->getId(),
			'title' => $data->getTitle(),
			'template' => $templateName,
			'path' => $pathPart,
			'icon' => 'page',
		);
		
		if ($page->hasChildren()) {
			$array['icon'] = 'folder';
			$array['children'] = array();
			
			foreach ($page->getChildren() as $child) {
				$array['children'][] = $this->buildTreeArray($child, $locale);
			}
		}
		
		return $array;
	}
}
