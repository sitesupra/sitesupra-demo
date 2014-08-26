<?php

namespace Project\FancyBlocks\BlogArchive;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Blog\BlogApplication;
use Supra\Controller\Pages\Entity\ApplicationPage;

use Supra\Controller\Pages\Entity\ApplicationLocalization;
use Supra\Controller\Pages\Application\PageApplicationCollection;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;
use Supra\Controller\Pages\Finder;

/**
 */
class BlogArchiveBlock extends BlockController
{

    const CONTEXT_PARAMETER_PERIOD = '__blogArchivePeriod';
    
	/**
	 * @var BlogApplication
	 */
	protected $blogApplication;
    
	protected function doPrepare()
	{
		$request = $this->getRequest();
		$context = $this->getResponse()
				->getContext();
        
		$period = $request->getQueryValue('period', null);
		$context->setValue(self::CONTEXT_PARAMETER_PERIOD, $period);
	}
    
    public function doExecute()
    {
        $response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */

        $application = $this->getBlogApplication();

        if ($application === null) {
            $response->outputTemplate('application-missing.html.twig');
            return null;
        }

		$localizationFinder = new Finder\LocalizationFinder(
				new Finder\PageFinder(ObjectRepository::getEntityManager($this)
		));
		
		$localizationFinder->addFilterByParent($application->getApplicationLocalization(), 1, 1);
		
		$qb = $localizationFinder->getQueryBuilder();
		
		$archives = $application->getFilterFolders($qb, 'byYear');
        /* @var $application \Supra\Controller\Pages\Blog\BlogApplication */
        
        $currentPeriod = $response->getContext()
				->getValue(self::CONTEXT_PARAMETER_PERIOD, null);
		
		$blogPath = $application
				->getApplicationLocalization()
				->getFullPath(\Supra\Uri\Path::FORMAT_BOTH_DELIMITERS);
        
        $response->assign('archive', $archives)
                ->assign('currentPeriod', $currentPeriod)
				->assign('blogPath', $blogPath)
                ->outputTemplate('index.html.twig');
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