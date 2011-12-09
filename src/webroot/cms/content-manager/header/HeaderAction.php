<?php

namespace Supra\Cms\ContentManager\Header;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Cms\ApplicationConfiguration;
use Supra\Cms\CmsApplicationConfiguration;
use Supra\Request;
use Supra\Response;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\AuthorizedControllerInterface;
use Supra\Authorization\AccessPolicy\AuthorizationAccessPolicyAbstraction;

class HeaderAction extends PageManagerAction
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function applicationsAction()
	{
		$config = CmsApplicationConfiguration::getInstance();
		$appConfigs = $config->getArray();
		
		$response = array();
		
		foreach ($appConfigs as $appConfig) {
			/* @var $appConfig ApplicationConfiguration */
			
			if($this->applicationIsVisible($this->getUser(), $appConfig)) {
				$response[] = $appConfig->getApplicationDataForInternalUserManager();
			}
		}
		
		$this->getResponse()->setResponseData($response);
	}
	
	/**
	 * @param type $applicationConfiguration
	 * @return integer
	 */
	private function applicationIsVisible($user, ApplicationConfiguration $appConfig)
	{
		if ($appConfig->authorizationAccessPolicy instanceof AuthorizationAccessPolicyAbstraction) {
			return $appConfig->authorizationAccessPolicy->isApplicationAdminAccessGranted($user);
		}
		else {
			return true;
		}
	}	
}