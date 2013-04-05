<?php

namespace Project\FancyBlocks\Form;

use Supra\Controller\Pages\BlockController;

class FormBlock extends BlockController
{

	public function doExecute()
    {
        $response = $this->getResponse();
        $response->outputTemplate('index.html.twig');
    }

}
