<?php

namespace Project\FancyBlocks\BlogTags;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Blog\BlogApplication;
use Supra\Controller\Pages\Entity\ApplicationPage;
use Supra\Controller\Pages\Entity\ApplicationLocalization;
use Supra\Controller\Pages\Application\PageApplicationCollection;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;

class BlogTagsBlock extends BlockController
{
	/**
	 * @var BlogApplication
	 */
	protected $blogApplication;
    
    /**
	 */
    public function doExecute()
    {
		$request = $this->getRequest();
		$response = $this->getResponse();
        /* @var $request \Supra\Controller\Pages\Request\PageRequest */
		
        $application = $this->getBlogApplication();
		
		if ( ! $application instanceof BlogApplication) {
			$response->outputTemplate('application-missing.html.twig');
			return null;
		}
		
		$tags = array();
		$activeTag = null;
		
		$blogLocalization = $application->getApplicationLocalization();
		$blogPath = $blogLocalization->getFullPath(\Supra\Uri\Path::FORMAT_BOTH_DELIMITERS);
		
		if ($request->isBlockRequest()) {
			// via ajax are requested only 'all' tags
			$tags = $application->getAllTagsArray();
		} else {

			$tags = $application->getPopularTagsArray();
			$activeTag = $request->getQueryValue('tag', null);
		}
		
		$response->assign('tags', $tags)
				->assign('activeTag', $activeTag)
				->assign('blogPath', $blogPath)
				->outputTemplate($request->isBlockRequest() ? 'json.html.twig' : 'index.html.twig');
    }
    
    
	/**
	 * @return BlogApplication
	 */
    protected function getBlogApplication()
    {
		if ($this->blogApplication === null) {
			
			$appLocalization = null;
			
			// if app page defined as property, we will use it
			if ($this->hasProperty('blog_page')) {
				$link = $this->getPropertyValue('blog_page');
				
				if ($link instanceof LinkReferencedElement) {
					$localization = $link->getPageLocalization();
					
					if ($localization instanceof ApplicationLocalization) {
						$appLocalization = $localization;
					}
				}
			}
			
			// if not found in properties, we'll try to find app from local environment
			if ($appLocalization === null) {
				$localization = $this->getRequest()
						->getPageLocalization();
				
				if ($localization instanceof ApplicationLocalization) {
					$appLocalization = $localization;
				} else {
					$parent = $localization->getMaster()
							->getParent();
					
					if ($parent instanceof ApplicationPage) {
						$appLocalization = $parent->getLocalization($localization->getLocale());
					}
				}
			}
			
			if ($appLocalization instanceof ApplicationLocalization) {
				$em = ObjectRepository::getEntityManager($this);
				$application = PageApplicationCollection::getInstance()
                        ->createApplication($appLocalization, $em);
				
				if ($application instanceof BlogApplication) {
					$this->blogApplication = $application;
				}
			}
		}
		
		return $this->blogApplication;
    }
}