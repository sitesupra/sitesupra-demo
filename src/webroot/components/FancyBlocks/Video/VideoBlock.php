<?php

namespace Project\FancyBlocks\Video;

use Supra\Controller\Pages\BlockController;
use Supra\Response;

/**
 * Video block
 */
class VideoBlock extends BlockController
{
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
        
        $response->assign('video', $this->getVideoData());
        
        // Local file is used
        $response->outputTemplate('index.html.twig');
    }
    
    protected function getVideoData()
    {
        $video = $this->getProperty('video');
        
        // Embed code
        return array(
            'resource' => 'source',
            'source'   => '<object width="560" height="315"><param name="movie" value="http://www.youtube.com/v/mdZo_keUoEs?hl=en_US&amp;version=3&amp;rel=0"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/mdZo_keUoEs?hl=en_US&amp;version=3&amp;rel=0" type="application/x-shockwave-flash" width="560" height="315" allowscriptaccess="always" allowfullscreen="true"></embed></object>',
        );
        
        // Youtube link:
        /*
        return array(
            'resource' => 'link',
            'service'  => 'youtube',     // extracted from url
            'id'       => 'mdZo_keUoEs', // extracted from url
        );
        */
        
        // Vimeo link
        /*
        return array(
            'resource' => 'link',
            'service'  => 'vimeo',
            'id'  => '56895025',
        );
        */
    }
}