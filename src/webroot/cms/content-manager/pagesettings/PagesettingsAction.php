<?php

namespace Supra\Cms\ContentManager\pagesettings;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;

/**
 * Page settings actions
 */
class PagesettingsAction extends PageManagerAction
{
	/**
	 * Saves page properties
	 */
	public function saveAction()
	{
		$this->isPostRequest();
		$pageData = $this->getPageData();
		
		//TODO: create some simple objects for save post data with future validation implementation?
		if ($this->hasRequestParameter('title')) {
			$title = $this->getRequestParameter('title');
			$pageData->setTitle($title);
		}
		
		if ($pageData instanceof Entity\PageData && $this->hasRequestParameter('path')) {
			$pathPart = $this->getRequestParameter('path');
			$pageData->setPathPart($pathPart);
		}
		
		$this->entityManager->flush();
	}
	
	/**
	 * List of templates
	 */
	public function templatesAction()
	{
		$locale = $this->getLocale();
		
		$templateDataDao = $this->entityManager->getRepository(PageRequest::TEMPLATE_DATA_ENTITY);
		$templateDataList = $templateDataDao->findByLocale($locale);
		
		/* @var $templateData Entity\TemplateData */
		foreach ($templateDataList as $templateData) {
			
			$templateArray = array(
				'id' => $templateData->getMaster()->getId(),
				'title' => $templateData->getTitle(),
				//TODO: hardcoded
				'img' => "/cms/lib/supra/img/templates/template-1.png"
			);
			
			$this->getResponse()->appendResponseData($templateArray);
		}
	}
}
