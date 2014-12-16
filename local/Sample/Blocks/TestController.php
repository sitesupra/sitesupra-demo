<?php

namespace Sample\Blocks;

class TestController extends \Supra\Package\Cms\Pages\BlockController
{
	protected function doExecute()
	{
		$image = $this->getProperty('images.0');

		$this->getResponse()->render();
	}
}