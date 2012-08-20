<?php

namespace Project\FancyBlocks\Gallery;

use Supra\Controller\Pages\BlockController;

class GalleryBlock extends BlockController
{
	public function doExecute()
	{
		$response = $this->getResponse();
		$response->outputTemplate('index.html.twig');
	}

}
