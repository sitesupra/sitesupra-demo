<?php

namespace Project\FancyBlocks\BlogPost;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Entity\ApplicationPage;

class BlogPostBlock extends BlockController
{
	/**
	 * @var \Supra\Controller\Pages\Blog\BlogApplication
	 */
	protected $blogApplication;
	
    
    protected function doPrepare()
    {
        $response = $this->getResponse();
        /* @var $response \Supra\Response\TwigResponse */
        $context = $response->getContext();
        /* @var $context \Supra\Response\ResponseContext */
        
        $description = $this->getPropertyValue('description');
        if ($description) {
            $context->setValue('metaDescription', $description);
        }    
    }
	
	protected function doExecute()
	{
		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */
        
		$application = $this->getBlogApplication();
		$appLocalizationPath = $application->getApplicationLocalization()
				->getPath();
		
		$response->assign('applicationPagePath', $appLocalizationPath)
				->outputTemplate('index.html.twig');
	}
	
	/**
	 * @return \Supra\Controller\Pages\Blog\BlogApplication
	 */
	private function getBlogApplication()
	{
		$request = $this->getRequest();
		
		$parentPage = $request->getPageLocalization()
				->getMaster()
				->getParent();
		
		if ($parentPage instanceof ApplicationPage) {
			
			$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($this);
			$localization = $parentPage->getLocalization($request->getLocale());
			
			$application = \Supra\Controller\Pages\Application\PageApplicationCollection::getInstance()
					->createApplication($localization, $em);
			
			return $application;
		}

		return null;
	}
}
