<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\BlockController;

class GalleryBlock extends BlockController
{
	public function doExecute()
	{
		$this->getResponse()
				->render();
//				->outputTemplate('SamplePackage:blocks/gallery.html.twig');
	}
}