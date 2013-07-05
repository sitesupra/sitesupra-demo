<?php

namespace Project\FancyBlocks\BlogTags;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Blog\BlogApplication;

use Supra\Controller\Pages\Entity\ApplicationLocalization;
use Supra\Controller\Pages\Application\PageApplicationCollection;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;

class BlogTagsBlock extends BlockController
{
		
	/**
	 * @var \Supra\Controller\Pages\Blog\BlogApplication
	 */
	protected $blogApplication;
    
    
    public function doExecute()
    {
        $tags = array();
		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */
        $request = $this->getRequest();
        /* @var $request \Supra\Controller\Pages\Request\PageRequestView */        
        $application = $this->getBlogApplication();
        
        if ($request->getQuery()->has('getAllTags')) {
            //Ajax request to get all tags
            if ($application !== null) {
                $tags = $application->getAllTagsArray();
            }
            
            $response->assign('data', array('tags' => $tags))
                    ->outputTemplate('json.html.twig');
            
        } else {
            //Normal page request
            $tag = $request->getQueryValue('tag', null);

            if ($application === null) {
                $response->outputTemplate('application-missing.html.twig');
                return;
            }

            $tags = $application->getPopularTagsArray();

            $response->assign('tags', $tags)
                    ->assign('currentTag', $tag)
                    ->assign('blogTagsBlock', $this->getBlock()->getId())
                    ->outputTemplate('index.html.twig');    
            }
    }
    
    
	/**
	 */
    protected function getBlogApplication()
    {
            
		if ($this->blogApplication === null) {
            
            $blogPage = $this->getPropertyValue('blog_page');
            
            if ($blogPage instanceof LinkReferencedElement) {
                $localization = $blogPage->getPageLocalization();
                
                if ($localization instanceof ApplicationLocalization) {

                    $em = ObjectRepository::getEntityManager($this);
                    $application = PageApplicationCollection::getInstance()
                        ->createApplication($localization, $em);

                    if ($application instanceof BlogApplication) {
                        $this->blogApplication = $application;
                    }
                }
            }
		}
		
		return $this->blogApplication;
    }
}