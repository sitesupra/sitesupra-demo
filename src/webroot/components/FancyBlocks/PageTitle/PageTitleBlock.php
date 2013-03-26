<?php
namespace Project\FancyBlocks\PageTitle;
        
use Supra\Controller\Pages\BlockController;

class PageTitleBlock extends BlockController
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
