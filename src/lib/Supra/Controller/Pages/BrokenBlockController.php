<?php

namespace Supra\Controller\Pages;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Request\PageRequestEdit;

class BrokenBlockController extends BlockController
{

	public function getPropertyDefinition()
	{
		return array();
	}

	public function execute()
	{
		$request = $this->getRequest();
		if ($request instanceof PageRequestEdit) {
			$this->getResponse()
					->output("This block was removed");
		}
	}
	
}