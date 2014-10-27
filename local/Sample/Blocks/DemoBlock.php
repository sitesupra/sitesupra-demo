<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\BlockController;

class DemoBlock extends BlockController
{
	public function doExecute()
	{
		$this->getResponse()
				->outputTemplate('SamplePackage:blocks/demo.html.twig');
	}
}