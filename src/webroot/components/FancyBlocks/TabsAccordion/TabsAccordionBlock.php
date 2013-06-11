<?php

namespace Project\FancyBlocks\TabsAccordion;

use Supra\Controller\Pages\BlockController;
use Supra\Response;

/**
 * Text block for news articles
 */
class TabsAccordionBlock extends BlockController
{
    public function doExecute()
    {
        $response = $this->getResponse();
        /* @var $response Response\TwigResponse */

        $response->assign('propertyMap', $this->getPropertyExistanceMap());
        
        // Local file is used
        $response->outputTemplate('index.html.twig');
    }
    
    protected function getPropertyExistanceMap()
    {
        $map = array();
        
        $titleKey = 'title_';
        $contentKey = 'content_';
        
        for ($i = 1; $i <= 50; $i++) {
            
            $titlePropertyName = $titleKey . $i;
            $contentPropertyName = $contentKey . $i;
            
            $titleValue = $this->getProperty($titlePropertyName)
                    ->getValue();
            
            $contentValue = $this->getProperty($contentPropertyName)
                    ->getValue();
            
            $map[$titlePropertyName] = ( ! empty($titleValue) ? true : false);
            $map[$contentPropertyName] = ( ! empty($contentValue) ? true : false);            
        }
        
        return $map;
    }
}