<?php

namespace Supra\Social\Facebook;

use Supra\Controller\Pages\BlockController;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Editable;

/**
 * Feedback block
 */
class FacebookBlock extends BlockController
{
	
	public function doExecute()
	{
		$response = $this->getResponse();
		$response->output('Facebook block');
	}

	public function getPropertyDefinition()
	{
		$properties = array();

		
		$html = new Editable\Select('Available pages');
		$properties['available_pages'] = $html;

		$html = new Editable\LabelString('Facebook tab name');
		$properties['tab_name'] = $html;
		
		return $properties;
	}

}