<?php

namespace Supra\Controller\Pages;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Request\PageRequestEdit;

class BrokenBlockController extends BlockController
{
	
	const BLOCK_NAME = 'Supra_Controller_Pages_BrokenBlockController';

	public function doExecute()
	{
		$request = $this->getRequest();
		if ($request instanceof PageRequestEdit) {
			$this->getResponse()
					->output("<p><span>This block was removed</span></p>");
		}
	}
	
}