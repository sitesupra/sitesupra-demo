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
        
        // Local file is used
        $response->outputTemplate('index.html.twig');
    }
}