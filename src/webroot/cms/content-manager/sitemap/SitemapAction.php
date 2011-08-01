<?php

namespace Supra\Cms\ContentManager\sitemap;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\CmsActionController;

/**
 * Sitemap
 */
class SitemapAction extends CmsActionController
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
			\Supra\Controller\Pages\Request\Request::PAGE_ENTITY,
			\Supra\Controller\Pages\Request\Request::TEMPLATE_ENTITY
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
//		[
//	{"id": 12, "title": "Home", "path": "", "icon": "home", "published": true, "scheduled": false, "children": [
//		{"id": 13, "title": "News", "path": "news", "icon": "news", "published": true, "scheduled": false},
//		{"id": 14, "title": "About us", "path": "section-1", "icon": "folder", "published": true, "scheduled": false, "children": [
//			{"id": 15, "title": "Company", "path": "page-1", "icon": "page", "published": true, "scheduled": false},
//			{"id": 16, "title": "Contacts", "path": "page-2", "icon": "page", "published": false, "scheduled": true}
//		]},
//		{"id": 17, "title": "Catalogue", "path": "catalogue", "icon": "cart", "published": true, "scheduled": true},
//		{"id": 18, "title": "Services", "path": "section-2", "icon": "folder", "published": true, "scheduled": false, "children": [
//			{"id": 19, "title": "Testimonials", "path": "page-3", "icon": "page", "published": true, "scheduled": false}
//		]},
//		{"id": 20, "title": "Products", "path": "section-3", "icon": "folder", "published": false, "scheduled": true, "isDragable": true, "isDropTarget": true, "children": [
//			{"id": 21, "is_dragable": false, "title": "Generic product", "path": "page-4", "icon": "page", "published": true, "scheduled": false, "isDragable": true, "isDropTarget": true}
//		]},
//		{"id": 22, "title": "Feedback form", "path": "feedback", "icon": "feedback", "published": true, "scheduled": false}
//	]}
//]
	}
	
	/**
	 * @param \Supra\Controller\Pages\Entity\Page $page
	 * @param string $locale
	 * @return array
	 */
	private function buildTreeArray(\Supra\Controller\Pages\Entity\Abstraction\Page $page, $locale)
	{
		$data = $page->getData($locale);
		$pathPart = null;
		$templateName = null;
		
		//TODO: need to know template ID as well
		if ($page instanceof \Supra\Controller\Pages\Entity\Page) {
			$templateName = $page->getTemplate()
					->getData($locale)
					->getTitle();
		}
		
		if ($data instanceof \Supra\Controller\Pages\Entity\PageData) {
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
