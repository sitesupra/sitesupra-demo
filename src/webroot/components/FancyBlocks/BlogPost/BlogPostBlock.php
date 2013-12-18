<?php

namespace Project\FancyBlocks\BlogPost;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Entity\ApplicationPage;
use Supra\Controller\Pages\Blog\BlogApplication;
use Supra\Controller\Pages\Entity\ReferencedElement\ImageReferencedElement;
use Supra\ObjectRepository\ObjectRepository;
use Supra\FileStorage\Entity\Image;

class BlogPostBlock extends BlockController
{
	/**
	 * @var \Supra\Controller\Pages\Blog\BlogApplication
	 */
	protected $blogApplication;
	
	/**
	 */
	protected function doExecute()
	{
		$response = $this->getResponse();
        /* @var $response \Supra\Response\TwigResponse */
		
		$application = $this->getBlogApplication();
		if ( ! $application instanceof BlogApplication) {
			$response->outputTemplate('application-missing.html.twig');
			return null;
		}
		
		$context = $response->getContext();
        
//        $description = $this->getPropertyValue('description');
//        if ($description) {
//            $context->setValue('metaDescription', $description);
//        }
        
        $mediaProperty = $this->getProperty('media');
        $metaCollection = $mediaProperty->getMetadata();

		if ( ! $metaCollection->isEmpty()) {
            
			$referencedElement = $metaCollection->first()
				->getReferencedElement();
			
            if ($referencedElement instanceof ImageReferencedElement) {
                $imageId = $referencedElement->getImageId();
                
                if ($imageId) {
                    $fileStorage = ObjectRepository::getFileStorage($this);
                    $image = $fileStorage->find($imageId);
                    if ($image instanceof Image) {
                        $context->setValue('metaImage', $imageId);    
                    }
                }
            }
        }
		
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
