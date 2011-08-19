<?php

namespace Supra\Cms\ContentManager\Pagesettings;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;
use DateTime;
use Supra\Cms\Exception\CmsException;

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
		$locale = $this->getLocale();
		
		//TODO: create some simple objects for save post data with future validation implementation?
		if ($this->hasRequestParameter('title')) {
			$title = $this->getRequestParameter('title');
			$pageData->setTitle($title);
		}
		
		if ($pageData instanceof Entity\PageData) {
		
			if ($this->hasRequestParameter('path')) {
				$pathPart = $this->getRequestParameter('path');
				$pageData->setPathPart($pathPart);
			}

			if ($this->hasRequestParameter('template')) {
				$templateId = $this->getRequestParameter('template');

				/* @var $template Entity\Template */
				$template = $this->entityManager->find(PageRequest::TEMPLATE_ENTITY, $templateId);

				$page = $pageData->getMaster();
				$page->setTemplate($template);
			}
		}
		
		if ($this->hasRequestParameter('active')) {
			$active = $this->getRequestParameter('active');
			$pageData->setActive($active);
		}
		
		if ($this->hasRequestParameter('description')) {
			$metaDescription = $this->getRequestParameter('description');
			$pageData->setMetaDescription($metaDescription);
		}
		
		if ($this->hasRequestParameter('keywords')) {
			$metaKeywords = $this->getRequestParameter('keywords');
			$pageData->setMetaKeywords($metaKeywords);
		}
		
		if ($this->hasRequestParameter('scheduled_date')) {
			
			$date = $this->getRequestParameter('scheduled_date');
			$time = $this->getRequestParameter('scheduled_time');
			
			if (empty($date)) {
				$pageData->unsetScheduleTime();
			} else {
				if (empty($time)) {
					$time = '00:00';
				}
				
				$dateTime = $date . $time;
				
				$scheduleTime = DateTime::createFromFormat('Y-m-dH:i', $dateTime);
				
				//TODO: Try other format, must remove when JS is fixed
				if (empty($scheduleTime)) {
					$scheduleTime = DateTime::createFromFormat('d.m.YH:i', $dateTime);
				}
				
				if ($scheduleTime instanceof DateTime) {
					$pageData->setScheduleTime($scheduleTime);
				} else {
					throw new CmsException(null, "Schedule time provided in unrecognized format");
				}
			}
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
