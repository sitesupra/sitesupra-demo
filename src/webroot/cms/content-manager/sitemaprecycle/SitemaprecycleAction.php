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
		$response = $this->getData(PageRequest::PAGE_ENTITY);
		
		$this->getResponse()
				->setResponseData($response);
	}
	
	public function templatesAction()
	{
		$response = $this->getData(PageRequest::TEMPLATE_ENTITY);
		
		$this->getResponse()
				->setResponseData($response);
	}
	
	
	public function restoreAction()
	{
		$this->restore();
	}
	
	
	protected function getData($entity)
	{
		$pages = array();
		
		$em = ObjectRepository::getEntityManager('Supra\Cms\Abstraction\Trash');
		$response = array();

		$pageRepository = $em->getRepository($entity);
		/* @var $pageRepository \Supra\Controller\Pages\Repository\PageRepository */
		$pages = $pageRepository->findAll();

		foreach ($pages as $page) {
			$dataCollection = $page->getDataCollection();

			$pageInfo = array(); $pageLocales = array();
			$pathPart = null;
			$templateId = null;
			
			foreach ($dataCollection as $pageLocalization) {
				if ( ! empty($pageLocales)) {
					$pageLocales[] = $pageLocalization->getLocale();
					
					continue;
				}
			
				if ($pageLocalization instanceof Entity\PageData) {
					$pathPart = $pageLocalization->getPathPart();
				}
			
				if ($page instanceof Entity\Page) {
					$templateId = $pageLocalization->getTemplate()
						->getId();
				}

				$pageInfo = array(
					'id'		=> $page->getId(),
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
				
				$pageLocales[] = $pageLocalization->getLocale();
			}
			
			// tmp | display locales
			if ( ! empty($pageInfo)) {
				$pageInfo['title'] = $pageInfo['title'] . '(' . implode(' | ', $pageLocales) . ')';
				$response[] = $pageInfo;
			}
		}
			
		return $response;
	}
	
}