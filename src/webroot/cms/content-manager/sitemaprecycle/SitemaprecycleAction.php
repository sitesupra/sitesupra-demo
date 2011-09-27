<?php

namespace Supra\Cms\ContentManager\Sitemaprecycle;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Exception\DuplicatePagePathException;
use Supra\Cms\Exception\CmsException;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Sitemap
 */
class SitemaprecycleAction extends PageManagerAction
{
	
	public function sitemapAction()
	{
		$response = $this->loadSitemapTree(Entity\PageLocalization::CN());
		
		$this->getResponse()
				->setResponseData($response);
	}
	
	public function templatesAction()
	{
		$response = $this->loadSitemapTree(Entity\TemplateLocalization::CN());
		
		$this->getResponse()
				->setResponseData($response);
	}
	
	
	public function restoreAction()
	{
		$this->restore();
	}
	
	
	protected function loadSitemapTree($entity)
	{
		$pages = array();
		
		$em = ObjectRepository::getEntityManager('Supra\Cms\Abstraction\Trash');
		$response = array();
		$localeId = $this->getLocale()->getId();

		$pageLocalizationRepository = $em->getRepository($entity);
		$pageLocalizations = $pageLocalizationRepository->findByLocale($localeId);

		foreach ($pageLocalizations as $pageLocalization) {

			$pageInfo = array();
			$pathPart = null;
			$templateId = null;
			
			if ($pageLocalization instanceof Entity\PageLocalization) {
				$pathPart = $pageLocalization->getPathPart();
			}

			if ($pageLocalization instanceof Entity\PageLocalization) {
				$templateId = $pageLocalization->getTemplate()
					->getId();
			}

			$pageInfo = array(
				'id'		=> $pageLocalization->getId(),
				'title'		=> $pageLocalization->getTitle(),
				'template'	=> $templateId,
				'path'		=> $pathPart,
				// TODO: hardcoded	
				'published' => false,
				'scheduled' => true,
				'date'		=> '2011-09-06',
				'version'	=> 1,
				'icon'		=> 'page',
				'preview'	=> '/cms/lib/supra/img/sitemap/preview/page-1.jpg',
			);
			
			$response[] = $pageInfo;
		}
			
		return $response;
	}
	
}