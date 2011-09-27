<?php

namespace Supra\Cms\InternalUserManager\Userpermissions;

use Supra\Controller\SimpleController;
use Supra\Cms\InternalUserManager\InternalUserManagerAbstractAction;
use Doctrine\ORM\EntityManager;
use Supra\User\Entity;
use Supra\User\UserProvider;
use Supra\Cms\CmsApplicationConfiguration;

class UserpermissionsAction extends InternalUserManagerAbstractAction
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

