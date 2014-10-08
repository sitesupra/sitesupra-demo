<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\BlockController;

class TextBlock extends BlockController
{
	public function doExecute()
	{
		$this->getResponse()
				->outputTemplate('SamplePackage:blocks/text.html.twig');
	}
}