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
		if (isset($parentId)) {
			$templateRepo = $this->entityManager->getRepository(PageRequest::TEMPLATE_ENTITY);
			$parent = $templateRepo->findOneById($parentId);

			if (empty($parentId)) {
				$this->getResponse()->setErrorMessage("Template not specified or found");

				return;
			}
		}
		$this->entityManager->flush();
		
		// Set parent
		if ( ! empty($parent)) {
			$template->moveAsLastChildOf($parent);
		}
		
		$this->entityManager->flush();
		
		$this->outputPage($templateData);
	}

	/**
	 * 
	 */
	public function saveAction()
	{
		
	}

	/**
	 * 
	 */
	public function deleteAction()
	{
		
	}

}