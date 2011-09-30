<?php

namespace Supra\Authorization\AccessPolicy;

use Supra\User\Entity\Abstraction\User;
use Supra\Cms\ApplicationConfiguration;
use Supra\Authorization\AuthorizationProvider;
use Supra\ObjectRepository\ObjectRepository;

abstract class AuthorizationAccessPolicyAbstraction
{
	const PERMISSION_NAME = "allow";

	/**
	 * @var AuthorizationProvider
	 */
	protected $ap;
	
	
	/**
	 * @var ApplicationConfiguration
	 */
	private $appConfig;
	
	/**
	 * @var array
	 */
	protected $permission;
	
	public function __construct() 
	{
		$this->ap = ObjectRepository::getAuthorizationProvider(get_called_class());
	}
	
	
	protected function getAppConfig() {
		
		if(empty($this->appConfig)) {
			$this->appConfig = ObjectRepository::getApplicationConfiguration(get_called_class());
		}
	
		return $this->appConfig;
	}
	
	public function getPermissionForInternalUserManager() 
	{
		return $this->permission;
	}
	
	abstract public function setAccessPermission(User $user, $value); 
	
	abstract public function getAccessPermission(User $user);
}
