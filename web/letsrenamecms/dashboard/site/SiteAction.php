<?php

namespace Supra\Cms\Dashboard\Site;

use Supra\Validator\Type\AbstractType;
use Supra\Cms\Exception\CmsException;
use Supra\Cms\Dashboard\DasboardAbstractAction;

class SiteAction extends DasboardAbstractAction
{
	
	/**
	 * List user sites 
	 * FIXME: This action should return UserSites @ SupraPortal
	 */
	public function sitesAction()
	{
		$sites = array();
				
		$this->getResponse()
				->setResponseData($sites);
	}
	
}
