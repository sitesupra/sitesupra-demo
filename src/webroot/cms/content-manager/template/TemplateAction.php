<?php

namespace Supra\Cms\ContentManager\Template;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Exception\DuplicatePagePathException;
use Supra\Cms\Exception\CmsException;

/**
 * Sitemap
 */
class TemplateAction extends PageManagerAction
{

	/**
	 * 
	 */
	public function createAction()
	{
		$this->isPostRequest();

		$parentId = $this->getRequestParameter('parent');
		$locale = $this->getLocale();

		$template = new Entity\Template();
		$templateData = new Entity\TemplateData($locale);

		$this->entityManager->persist($template);
		$this->entityManager->persist($templateData);

		$templateData->setMaster($template);

		if ($this->hasRequestParameter('title')) {
			$title = $this->getRequestParameter('title');
			$templateData->setTitle($title);
		}

		// Find parent page
		$parent = null;
		
		if ( ! empty($parentId)) {
			$templateRepo = $this->entityManager->getRepository(PageRequest::TEMPLATE_ENTITY);
			$parent = $templateRepo->findOneById($parentId);
		} else {
			//TODO: receive from ui
			$file = 'root.html';
			
			// Search for this layout
			$layoutRepo = $this->entityManager->getRepository('Supra\Controller\Pages\Entity\Layout');
			$layout = $layoutRepo->findOneByFile($file);
			
			if (is_null($layout)) {
				//TODO: try finding such layout, find all placeholders, create layout and placeholder records in db
				// Raises exception for now
				throw new \RuntimeException("Layout not found");
			}
			
			$template->addLayout(Entity\Layout::MEDIA_SCREEN, $layout);
//			
//			$layout = new Entity\Layout();
//			$this->entityManager->persist($layout);
//			$layout->setFile('root.html');
//
//			foreach (array('header', 'main', 'footer', 'sidebar') as $name) {
//				$layoutPlaceHolder = new Entity\LayoutPlaceHolder($name);
//				$layoutPlaceHolder->setLayout($layout);
//			}
		}
		
		$this->entityManager->flush();

		// Set parent
		if ( ! empty($parent)) {
			$template->moveAsLastChildOf($parent);
			$this->entityManager->flush();
		}

		$this->outputPage($templateData);
	}

	/**
	 * 
	 */
	public function saveAction()
	{
		$this->isPostRequest();
		$pageData = $this->getPageData();
		$locale = $this->getLocale();

		//TODO: create some simple objects for save post data with future validation implementation?
		if ($this->hasRequestParameter('title')) {
			$title = $this->getRequestParameter('title');
			$pageData->setTitle($title);
		}

		$this->entityManager->flush();
	}

	/**
	 * 
	 */
	public function deleteAction()
	{
		
	}

	/**
	 * Called on template publish
	 */
	public function publishAction()
	{
		$this->publish();
	}

}