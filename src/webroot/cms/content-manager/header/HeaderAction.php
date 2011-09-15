<?php

namespace Supra\Cms\ContentManager\Header;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Cms\CmsApplicationConfiguration;
use Supra\Request;
use Supra\Response;

class HeaderAction extends PageManagerAction
{

	public function applicationsAction()
	{
		$config = CmsApplicationConfiguration::getInstance();
		$response = $config->toArray();
		
		$this->getResponse()->setResponseData($response);
	}

}