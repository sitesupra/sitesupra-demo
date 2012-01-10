<?php

namespace Supra\Controller\Pages;

class MissingBlockController extends BlockController
{

	public function getPropertyDefinition()
	{
		return array();
	}

	public function execute()
	{
		$request = $this->getRequest();
		if ($request instanceof Request\PageRequestEdit) {
			$this->getResponse()
					->output("This block was removed");
		}
	}
	
}