<?php

namespace Project\FancyBlocks\Logotype;

use Supra\Controller\Pages\BlockController;
use Supra\Editable;

class LogotypeBlock extends BlockController
{
    public function doExecute()
    {
        $response = $this->getResponse();
        $response->outputTemplate('index.html.twig');
    }
}
