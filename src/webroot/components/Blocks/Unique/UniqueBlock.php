<?php

namespace Project\Blocks\Unique;

use Supra\Controller\Pages\BlockController;

/**
 * 
 */
class UniqueBlock extends BlockController
{
	public function doExecute()
	{
		$this->getResponse()
				->output("<div><h3>Unique block content</h3></div>");
	}
}
