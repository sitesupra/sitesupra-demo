<?php

namespace Project\Blocks\Gallery;

use Supra\Controller\Pages\BlockController;
use Supra\Request;
use Supra\Response;
use Supra\Editable;
use Supra\Uri\PathConvertor;

class GalleryBlock extends BlockController
{

	public function doExecute()
	{
		$response = $this->getResponse();
		$context = $response->getContext();

		$context->addCssLinkToLayoutSnippet('css', PathConverter::getWebPath($this, 'css/style.css'));
		$response->outputTemplate('index.html.twig');
	}

}
