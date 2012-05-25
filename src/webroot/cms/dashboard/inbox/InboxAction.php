<?php

namespace Supra\Cms\Dashboard\Inbox;

use Supra\Cms\Dashboard\DasboardAbstractAction;

class InboxAction extends DasboardAbstractAction
{
	
	public function inboxAction()
	{
		
		$response = array(
			array(
				"title" => "Gallery block is due in 3 days",
				"buy" => true,
				"new" => true,
			),
			array(
				"title" => "Gallery block is due in 3 days",
				"buy" => true,
				"new" => true,
			),
			array(
				"title" => "Gallery block is due in 3 days",
				"buy" => true,
				"new" => false,
			),
		);
		
		$this->getResponse()
				->setResponseData($response);	
	}
	
}