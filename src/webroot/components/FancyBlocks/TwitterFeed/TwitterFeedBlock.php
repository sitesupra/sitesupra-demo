<?php

namespace Project\FancyBlocks\TwitterFeed;

use Supra\Controller\Pages\BlockController;

class TwitterFeedBlock extends BlockController
{

	public function doExecute()
    {
        $response = $this->getResponse();
        $response->outputTemplate('index.html.twig');
    }

}
