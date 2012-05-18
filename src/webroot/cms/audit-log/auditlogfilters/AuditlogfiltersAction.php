<?php

namespace Supra\Cms\AuditLog\Auditlogfilters;

use Supra\Controller\SimpleController;
use Supra\Request;
use Supra\Response;
use Supra\Cms\CmsAction;

/**
 *
 */
class AuditlogfiltersAction extends CmsAction
{
	
	/**
	 * Basic method to get list of components (CMS applications)
	 * which will be used as dropdown components filter
	 */
	public function componentsAction()
	{
		$components = array();
		
		$config = \Supra\Cms\CmsApplicationConfiguration::getInstance();
		$appConfigs = $config->getArray();
		
		foreach($appConfigs as $config) {
			/* @var $config ApplicationConfiguration */
			$components[] = array(
				'title' => $config->title,
				'id' => $config->class,
			);
		}
			
		$this->getResponse()
				->setResponseData($components);
	}

}