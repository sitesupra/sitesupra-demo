<?php

namespace Project\FancyBlocks\BlogPost;

use Supra\Controller\Pages\BlockController;

class BlogPostBlock extends BlockController
{
    
    /**
     * @return array
     */
    public static function getPropertyDefinition()
    {
        $properties = array();

        return $properties;
    }

    protected function doExecute()
    {
        $response = $this->getResponse();
        /* @var $response \Supra\Response\TwigResponse */

        $response->outputTemplate('index.html.twig');
    }
    
}
