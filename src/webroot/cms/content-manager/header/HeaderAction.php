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
	var $authorizationProvider;
	
	function __construct() {
		
		parent::__construct();
		$this->authorizationProvider = ObjectRepository::getAuthorizationProvider($this);
	}
	
	public function applicationsAction()
	{
		$config = CmsApplicationConfiguration::getInstance();
		$appConfigs = $config->getArray();
		
		$response = array();
		
		foreach ($appConfigs as $appConfig) {
			
			if($this->applicationIsVisible($this->getUser(), $appConfig)) {
				$response[] = get_object_vars($appConfig);
			}
		}
		
		$this->getResponse()->setResponseData($response);
	}
	
	/**
	 * @param type $applicationConfiguration
	 * @return integer
	 */
	function applicationIsVisible($user, ApplicationConfiguration $appConfig)
	{
		if ( $appConfig->authorizationAccessPolicy instanceof AuthorizationAccessPolicyAbstraction) {
			return $this->authorizationProvider->isApplicationAdminAccessGranted($user, $appConfig);
		}
		else {
			return true;
		}
	}	
}