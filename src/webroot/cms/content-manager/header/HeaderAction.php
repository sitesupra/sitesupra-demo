<?php

namespace Supra\Cms\ContentManager\Header;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Cms\ApplicationConfiguration;
use Supra\Cms\CmsApplicationConfiguration;
use Supra\Request;
use Supra\Response;

class HeaderAction extends PageManagerAction
{

	public function applicationsAction()
	{
		$config = CmsApplicationConfiguration::getInstance();
		$appConfigs = $config->getArray();

		$response = array();
		
		foreach ($appConfigs as $appConfig) {
			$response[] = get_object_vars($appConfig);
		}
		
		$this->getResponse()->setResponseData($response);
	}

}