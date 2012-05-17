<?php

namespace Supra\Cms\AuditLog\Auditlogfilters;

use Supra\Controller\SimpleController;
use Supra\Request;
use Supra\Response;
use Supra\Cms\CmsAction;

/**
 * Root action, returns initial HTML
 * @method TwigResponse getResponse()
 */
class AuditlogfiltersAction extends CmsAction
{

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