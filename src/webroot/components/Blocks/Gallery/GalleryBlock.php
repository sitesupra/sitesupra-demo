<?php

namespace Project\Blocks\Gallery;

use Supra\Controller\Pages\BlockController,
	Supra\Request,
	Supra\Response;

class GalleryBlock extends BlockController
{

	public function doExecute()
	{
		$response = $this->getResponse();
		$response->outputTemplate('index.html.twig');
	}

}
