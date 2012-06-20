<?php

namespace Project\Blocks\Gallery;

use Supra\Controller\Pages\BlockController;
use Supra\Request;
use Supra\Response;
use Supra\Editable;

class GalleryBlock extends BlockController
{
	public function doExecute()
	{
		$response = $this->getResponse();
		$context = $response->getContext();
		
		$dir = str_replace(SUPRA_WEBROOT_PATH, '', __DIR__);
		$context->addCssLinkToLayoutSnippet('css', "/{$dir}/css/style.css");
		
		$response->outputTemplate('index.html.twig');
	}

}
