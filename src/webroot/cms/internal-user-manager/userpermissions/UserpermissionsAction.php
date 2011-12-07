<?php

namespace Supra\Cms\InternalUserManager\Userpermissions;

use Supra\Controller\SimpleController;
use Supra\Cms\InternalUserManager\InternalUserManagerAbstractAction;
use Doctrine\ORM\EntityManager;
use Supra\User\Entity;
use Supra\User\UserProvider;
use Supra\Cms\CmsApplicationConfiguration;
use Supra\Cms\ApplicationConfiguration;
use Supra\ObjectRepository\ObjectRepository;

class UserpermissionsAction extends InternalUserManagerAbstractAction
{
	public function applicationsAction()
	{
		/* @var $internalUserManagerAppConfig ApplicationConfiguration */
		$internalUserManagerAppConfig = ObjectRepository::getApplicationConfiguration($this);
		
		$config = CmsApplicationConfiguration::getInstance();
		$appConfigs = $config->getArray();

		$response = array();
		
		/* @var $appConfig ApplicationConfiguration */
		foreach ($appConfigs as $appConfig) {
			
			if($appConfig->id != $internalUserManagerAppConfig->id) {
				$response[] = $appConfig->getApplicationDataForInternalUserManager();
			}
		}
		
		$this->getResponse()->setResponseData($response);		
	}
}

