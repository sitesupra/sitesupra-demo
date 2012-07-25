<?php

namespace Project\FancyBlocks\Gallery;

use Supra\Controller\Pages\BlockController;
use Supra\Request;
use Supra\Response;
use Supra\Editable;

class GalleryBlock extends BlockController
{
	public function doExecute()
	{
		$response = $this->getResponse();
		$response->outputTemplate('index.html.twig');
	}

}
