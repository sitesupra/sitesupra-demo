<?php

namespace Project\FancyBlocks\Text;

use Supra\Controller\Pages\BlockController;

/**
 * Html content block
 */
class TextController extends BlockController
{

	public function doExecute()
	{
		$this->getResponse()
				->outputTemplate('index.html.twig');
	}
}
