<?php

namespace Project\FancyBlocks\BlogPost;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Entity\ApplicationPage;
use Supra\Controller\Pages\Blog\BlogApplication;

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
            $context->setValue('metaDescription', $description->__toString());
        }    
    }
	
	protected function doExecute()
	{
		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */
        
		$response->outputTemplate('index.html.twig');
	}
}
