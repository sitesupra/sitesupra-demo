<?php

namespace Project\FancyBlocks\GalleryAdvanced;

use Supra\Controller\Pages\BlockController;

/**
 *
 */
class GalleryAdvancedBlock extends BlockController
{
	protected function doExecute()
	{
		$mediaItems = $this->getPropertyValue('media');
		
		$this->getResponse()
				->assign('items', $mediaItems)
				->outputTemplate('index.html.twig');
	}
}
