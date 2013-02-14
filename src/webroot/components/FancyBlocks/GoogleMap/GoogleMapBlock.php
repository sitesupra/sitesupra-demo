<?php

namespace Project\FancyBlocks\GoogleMap;

use Supra\Controller\Pages\BlockController;
use Supra\Response;

/**
 * Text block for news articles
 */
class GoogleMapBlock extends BlockController
{
    public function doExecute()
    {
        $response = $this->getResponse();
        /* @var $response Response\TwigResponse */

        $response->outputTemplate('index.html.twig');
    }
}