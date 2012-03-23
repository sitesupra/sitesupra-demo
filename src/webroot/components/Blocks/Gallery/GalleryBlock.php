<?php

namespace Project\Blocks\Gallery;

use Supra\Controller\Pages\BlockController;
use Supra\Request;
use Supra\Response;
use Supra\Editable;

class GalleryBlock extends BlockController
{
	public function getPropertyDefinition()
	{
		$images = new Editable\Gallery('Images');
		
		return array('images' => $images);
	}
	
	public function doExecute()
	{
		$response = $this->getResponse();
		$response->outputTemplate('index.html.twig');
	}

}
