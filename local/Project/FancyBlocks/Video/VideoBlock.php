<?php

namespace Project\FancyBlocks\Video;

/**
 * Video block
 */
class VideoBlock extends \Supra\Controller\Pages\BlockController
{
    public function doExecute()
    {
        $this->getResponse()->outputTemplate('index.html.twig');
    }
}