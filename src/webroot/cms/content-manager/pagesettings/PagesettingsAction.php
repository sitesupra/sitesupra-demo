<?php

namespace Supra\Cms\ContentManager\pagesettings;

use Supra\Cms\ContentManager\PageManagerAction;
use \Supra\Controller\Pages\Entity;

/**
 * Page settings actions
 */
class PagesettingsAction extends \Supra\Cms\ContentManager\PageManagerAction
{
	/**
	 * Saves page properties
	 */
	public function saveAction()
	{
		$this->isPostRequest();
		$pageData = $this->getPageData();
		
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
}
