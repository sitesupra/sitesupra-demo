<?php

namespace Supra\Authorization\AccessPolicy;

use Supra\User\Entity\Abstraction\User;
use Supra\Cms\ApplicationConfiguration;

abstract class AuthorizationAccessPolicyAbstraction
{
	const PERMISSION_NAME = "allow";
	
	/**
	 * @var ApplicationConfiguration
	 */
	protected $applicationConfiguration;
	
	/**
	 * @var array
	 */
	protected $permission;
	
	public function setApplicationConfiguration(ApplicationConfiguration $applicationConfiguration) 
	{
		$this->applicationConfiguration = $applicationConfiguration;
	}
	
	public function getAccessPermission() 
	{
		return $this->permission;
	}
	
	public function isVisibleForUser(User $user) 
	{
		return true;
	}
}
