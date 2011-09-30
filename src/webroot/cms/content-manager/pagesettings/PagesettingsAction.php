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
		$pageData = $this->getPageLocalization();
		$localeId = $this->getLocale()->getId();

		//TODO: create some simple objects for save post data with future validation implementation?
		if ($this->hasRequestParameter('title')) {
			$title = $this->getRequestParameter('title');
			$pageData->setTitle($title);
		}

		if ($pageData instanceof Entity\PageLocalization) {

			if ($this->hasRequestParameter('path')) {
				$pathPart = $this->getRequestParameter('path');
				$pageData->setPathPart($pathPart);
			}

			if ($this->hasRequestParameter('template')) {
				$templateId = $this->getRequestParameter('template');

				/* @var $template Entity\Template */
				$template = $this->entityManager->find(PageRequest::TEMPLATE_ENTITY, $templateId);
				$pageData->setTemplate($template);
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

					$dateTime = "{$date}T{$time}";

					$scheduleTime = new DateTime($dateTime);

					if ($scheduleTime instanceof DateTime) {
						$pageData->setScheduleTime($scheduleTime);
					} else {
						throw new CmsException(null, "Schedule time provided in unrecognized format");
					}
				}
			}
			
			if ($this->hasRequestParameter('created_date')) {

				$date = $this->getRequestParameter('created_date');
				$time = $this->getRequestParameter('created_time');

				// Set manually only if both elements are received
				if ( ! empty($date) && ! empty($time)) {
					$dateTime = "{$date}T{$time}";

					$creationTime = new DateTime($dateTime);

					if ($creationTime instanceof DateTime) {
						$pageData->setCreationTime($creationTime);
					} else {
						throw new CmsException(null, "Creation time provided in unrecognized format");
					}
				}

			}
			
			$redirect = $this->getRequestParameter('redirect');
		
			if ( ! is_null($redirect)) {

				// Delete current link object
				$currentRedirect = $pageData->getRedirect();

				if ( ! empty($currentRedirect)) {
					$this->entityManager->remove($currentRedirect);
				}

				// Set new link, JS should send empty value if link must be removed
				if (empty($redirect)) {
					$pageData->setRedirect(null);
				} else {
					$link = new Entity\ReferencedElement\LinkReferencedElement();
					$link->fillArray($redirect);
					$this->entityManager->persist($link);

					$pageData->setRedirect($link);
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
		$localeId = $this->getLocale()->getId();

		$templateDataDao = $this->entityManager->getRepository(PageRequest::TEMPLATE_DATA_ENTITY);
		$templateDataList = $templateDataDao->findByLocale($localeId);

		/* @var $templateData Entity\TemplateLocalization */
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
