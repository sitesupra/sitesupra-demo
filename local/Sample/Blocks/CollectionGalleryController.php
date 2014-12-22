<?php

namespace Sample\Blocks;

class CollectionGalleryController extends \Supra\Package\Cms\Pages\BlockController
{
	protected function doExecute()
	{
		$this->getResponse()->render();
	}
}