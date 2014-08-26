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
		$request = $this->getRequest();
		$blockRequest = $request->isBlockRequest();
		$response = $this->getResponse();
		
		if ($blockRequest) {
			$response->outputTemplate('load.html.twig');
		} else {
			$response->outputTemplate('index.html.twig');
		}
	}
}
