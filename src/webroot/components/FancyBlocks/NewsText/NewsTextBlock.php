<?php

namespace Project\FancyBlocks\NewsText;

use Supra\Controller\Pages\BlockController;
use Supra\Response;
use Supra\ObjectRepository\ObjectRepository;
use \Supra\FileStorage\Entity\Image;

/**
 * Text block for news articles
 */
class NewsTextBlock extends BlockController
{
    
    public function doPrepare()
    {
		$response = $this->getResponse();
		/* @var $response Response\TwigResponse */
        $context = $response->getContext();
        
        $imageId = $this->getPropertyValue('image');
        if ($imageId) {
            $fileStorage = ObjectRepository::getFileStorage($this);
            $image = $fileStorage->find($imageId);
            if ($image instanceof Image) {
                $image->getId();
                $context->setValue('metaImage', $imageId);    
            }
        }
    }
    
	public function doExecute()
	{
		$response = $this->getResponse();
		/* @var $response Response\TwigResponse */

		$theme = $this->getRequest()->getLayout()->getTheme();
		
		$response->getContext()
				->addCssLinkToLayoutSnippet('css', $theme->getUrlBase() . '/assets/css/page-news.css');
				
		// Local file is used
		$response->outputTemplate('index.html.twig');
	}
}
