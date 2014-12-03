<?php

namespace Supra\Package\Cms\Pages\Block;

use Supra\Package\Cms\Pages\BlockController;

class DefaultBlockController extends BlockController
{
	protected function doExecute()
	{
		$this->getResponse()->render();
	}
}