<?php

namespace Supra\Cms\ContentManager\Template;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Exception\DuplicatePagePathException;
use Supra\Cms\Exception\CmsException;
use Supra\Controller\Pages\Request\PageRequestEdit;

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
		$localeId = $this->getLocale()->getId();

		$template = new Entity\Template();
		$templateData = new Entity\TemplateData($localeId);

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
				//TODO:  CmsException?
				$layout = new \Supra\Controller\Pages\Entity\Layout();
				$this->entityManager->persist($layout);
				$layout->setFile($file);
				$processor = $this->getLayoutProcessor();
				$places = $processor->getPlaces($file);
				foreach ($places as $name) {
					$placeHolder = new \Supra\Controller\Pages\Entity\LayoutPlaceHolder($name);
					$placeHolder->setLayout($layout);
				}
			}
			
			$template->addLayout(Entity\Layout::MEDIA_SCREEN, $layout);
		}
		
		$this->entityManager->flush();

		// Set parent
		if ( ! empty($parent)) {
			$template->moveAsLastChildOf($parent);
			$this->entityManager->flush();
		}
		
		// Decision in #2695 to publish the template right after creating it
		$this->pageData = $templateData;
		$this->publish();

		$this->outputPage($templateData);
	}

	/**
	 * Settings save action
	 */
	public function saveAction()
	{
		$this->isPostRequest();
		$pageData = $this->getPageData();
		$localeId = $this->getLocale()->getId();

		//TODO: create some simple objects for save post data with future validation implementation?
		if ($this->hasRequestParameter('title')) {
			$title = $this->getRequestParameter('title');
			$pageData->setTitle($title);
		}

		$this->entityManager->flush();
	}

//	/**
//	 * Not implemented
//	 */
//	public function deleteAction()
//	{
//		
//	}

	/**
	 * Called on template publish
	 */
	public function publishAction()
	{
		// Must be executed with POST method
		$this->isPostRequest();
		
		$this->publish();
	}

	/**
	 * @return Supra\Controller\Layout\Processor\ProcessorInterface
	 */
	protected function getLayoutProcessor()
	{
		$processor = new \Supra\Controller\Layout\Processor\HtmlProcessor();
		$processor->setLayoutDir(\SUPRA_PATH . 'template');
		return $processor;
	}

}