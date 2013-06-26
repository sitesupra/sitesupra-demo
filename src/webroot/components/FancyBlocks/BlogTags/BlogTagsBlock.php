<?php

namespace Project\FancyBlocks\BlogTags;

use Supra\Controller\Pages\BlockController;

class BlogTagsBlock extends BlockController
{
    public function doExecute()
    {
        $response = $this->getResponse();
        
        $response->assign('popularTags', array(
            'vacation', 'holiday trip', 'mountains', 'hills',
            'exotic leisure', 'cats', 'business',
        ));
        
        $response->assign('allTags', array(
            'vacation', 'holiday trip', 'mountains', 'hills',
            'exotic leisure', 'cats', 'business', 'dogs',
            'love', 'water', 'hydrants', 'and', 'chasing', 'cars'
        ));
        
        $response->assign('currentTag', 'exotic leisure');
        
        $response->outputTemplate('index.html.twig');
    }
}
            
        