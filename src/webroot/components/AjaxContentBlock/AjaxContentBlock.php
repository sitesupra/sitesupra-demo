<?php

namespace Project\AjaxContentBlock;

use Supra\Controller\Pages\BlockController;
use Supra\Response;

/**
 * Ajax content block
 */
class AjaxContentBlock extends BlockController
{
	
	public function doExecute()
	{
		$response = $this->getResponse();
		// Local file is used
		$response->outputTemplate('index.html.twig');
	}
}
