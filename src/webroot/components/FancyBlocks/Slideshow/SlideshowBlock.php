<?php

namespace Project\FancyBlocks\Slideshow;

use Supra\Controller\Pages\BlockController;

class SlideshowBlock extends BlockController
{
	protected function doExecute()
	{
		$this->getResponse()->outputTemplate('index.html.twig');
	}
}
