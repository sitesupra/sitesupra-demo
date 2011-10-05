<?php

namespace Supra\Cms\ContentManager\Template;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Exception\DuplicatePagePathException;
use Supra\Cms\Exception\CmsException;
use Supra\Controller\Layout\Exception as LayoutException;

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
		$templateData = new Entity\TemplateLocalization($localeId);

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
				$layout = new \Supra\Controller\Pages\Entity\Layout();
				$this->entityManager->persist($layout);
				$layout->setFile($file);
				$processor = $this->getLayoutProcessor();
				try {
					$places = $processor->getPlaces($file);
					foreach ($places as $name) {
						$placeHolder = new \Supra\Controller\Pages\Entity\LayoutPlaceHolder($name);
						$placeHolder->setLayout($layout);
					}
				} catch (LayoutException\LayoutNotFoundException $e) {
					throw new CmsException('template.error.layout_not_found');
				} catch (LayoutException\RuntimeException $e) {
					throw new CmsException('template.error.layout_error');
				} catch (\Exception $e) {
					throw new CmsException('template.error.create_internal_error');
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
		$this->checkLock();
		$pageData = $this->getPageLocalization();
		$localeId = $this->getLocale()->getId();

		//TODO: create some simple objects for save post data with future validation implementation?
		if ($this->hasRequestParameter('title')) {
			$title = $this->getRequestParameter('title');
			$pageData->setTitle($title);
		}

		$this->entityManager->flush();
	}

	public function deleteAction()
	{
		$this->isPostRequest();

		$page = $this->getPageLocalization()->getMaster();
		$pageId = $page->getId();

		// Check if there is no children
		$hasChildren = $page->hasChildren();

		if ($hasChildren) {
			$this->getResponse()
					->setErrorMessage("Cannot remove template with children");

			return;
		}
		
		// TODO: remove from controller
		// TODO: or loop through array of pages (founded by findAll in PAGE_DATA_ENTITY repo)
		// and compare getTemplate()->getId();
		$pageDataEntity = Entity\PageLocalization::CN();
		$dql = "SELECT COUNT(p.id) FROM $pageDataEntity p 
				WHERE p.template = ?0";
		
		$count = $this->entityManager->createQuery($dql)
				->setParameters(array($pageId))
					->getSingleScalarResult();
		
		if ((int) $count > 0) {
			$this->getResponse()
					->setErrorMessage("Cannot remove template as there are pages using it");
			return;
		}

		$this->delete();
	}

	/**
	 * Called on template publish
	 */
	public function publishAction()
	{
		// Must be executed with POST method
		$this->isPostRequest();
		
		$this->checkLock();
		$this->publish();
		$this->unlockPage();
	}
	
	/**
	 * Called on template lock action
	 */
	public function lockAction()
	{
		$this->lockPage();	
	}
	
	/** 
	 * Called on template unlock action
	 */
	public function unlockAction()
	{
		try {
			$this->checkLock();
		} catch (\Exception $e) {
			$this->getResponse()->setResponseData(true);
			return;
		}
		$this->unlockPage();
	}

	/**
	 * @return Supra\Controller\Layout\Processor\ProcessorInterface
	 */
	protected function getLayoutProcessor()
	{
		$processor = new \Supra\Controller\Layout\Processor\HtmlProcessor();
		// FIXME: hardcode
		$processor->setLayoutDir(\SUPRA_PATH . 'template');
		return $processor;
	}

}
