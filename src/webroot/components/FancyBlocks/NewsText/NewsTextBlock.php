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

		// DEV comment about the block
		$block = $this->getBlock();
		$comment = '';
		if ( ! empty($block)) {
			$comment .= "Block $block.\n";
			if ($block->getLocked()) {
				$comment .= "Block is locked.\n";
			}
			if ($block->getPlaceHolder()->getLocked()) {
				$comment .= "Place holder is locked.\n";
			}
			$comment .= "Master " . $block->getPlaceHolder()->getMaster()->__toString() . ".\n";
		}
		
		$response->assign('comment', $comment);
		
		$theme = $this->getRequest()->getLayout()->getTheme();
		
		$response->getContext()
				->addCssLinkToLayoutSnippet('css', $theme->getUrlBase() . '/assets/css/page-news.css');
				
		// Local file is used
		$response->outputTemplate('index.html.twig');
	}
}
