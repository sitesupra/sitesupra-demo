<?php

namespace Supra\Cms\Settings\Sitesettings;

use Supra\Controller\SimpleController;
use Supra\Request;
use Supra\Response;
use Supra\Cms\CmsAction;


class SitesettingsAction extends CmsAction
{

	public function loadAction()
	{
		$this->getResponse()
				->setResponseData(array());
	}
	
	public function saveAction()
	{
		$this->getResponse()
				->setResponseData(array());
	}
	
	public function deleteAction()
	{
		$this->getResponse()
				->setResponseData(true);
	}
	
}
