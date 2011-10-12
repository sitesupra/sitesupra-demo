<?php

namespace Supra\Authorization\AccessPolicy;

use Supra\User\Entity\Abstraction\User;
use Supra\Cms\ApplicationConfiguration;
use Supra\Authorization\AuthorizationProvider;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\Permission\Application\ApplicationAllAccessPermission;
use Supra\Authorization\Permission\Application\ApplicationSomeAccessPermission;
use Supra\Authorization\Permission\Application\ApplicationExecuteAccessPermission;
use Supra\Request\HttpRequest;
use Supra\Request\RequestInterface;
use Supra\Log\Log;
use Supra\Authorization\Permission\PermissionStatus;

abstract class AuthorizationAccessPolicyAbstraction
{
	const APPLICATION_ACCESS_ID = 'allow';
	const PROPERTY_NAME = 'property';
	const VALUE_NAME = 'value';

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
	
	/**
	 * Logger
	 * @var Log;
	 */
	protected $log;
	
	public function __construct() 
	{
		$this->log = ObjectRepository::getLogger($this);
	}
	
	/**
	 * Configures base access policy. When extending, constructor signature must remain the same
	 * @param ApplicationConfiguration $appConfig
	 * @param AuthorizationProvider $ap 
	 */
	public function configure() 
	{
		$this->permission = array(
			"id" => self::APPLICATION_ACCESS_ID,
			"label" => "{#userpermissions.label_persmissions#}",
			"value" => "0"
		);	
	}

	/**
	 * Sets authorization provider to be used with this authorization access policy.
	 * @param AuthorizationProvider $ap 
	   */						
	public function setAuthorizationProvider(AuthorizationProvider $ap)
	{
		$this->ap = $ap;
	}

	/**
	 * Sets application configuration for this authorization access policy.
	 * @param ApplicationConfiguration $appConfig 
	 */
	public function setAppConfig(ApplicationConfiguration $appConfig)
	{
		$this->appConfig = $appConfig;
	}

	public function getPermissionForInternalUserManager()
	{
		return $this->permission;
	}

	abstract public function getAccessPolicy(User $user);
	
	abstract public function updateAccessPolicy(User $user, RequestInterface $updateRequest);
	
	/**
	 * Sets "ALL"  access for given user to application. Revokes "SOME" and "EXECUTE" if granted.
	 * @param User $user
	 */
	public function grantApplicationAllAccessPermission(User $user)
	{
		$this->log->debug('Granting application access "ALL" to ' . $this->appConfig->id . ' for user ' . $user->getName());

		$this->revokeApplicationSomeAccessPermission($user);
		$this->revokeApplicationExecuteAccessPermission($user);

		$this->ap->setPermsissionStatus($user, $this->appConfig, ApplicationAllAccessPermission::NAME, PermissionStatus::ALLOW);
	}

	/**
	 * Grants "SOME" access for given user to application. Revokes "ALL" and "EXECUTE" access if granted.
	 * @param User $user
	 */
	public function grantApplicationSomeAccessPermission(User $user)
	{
		$this->log->debug('Granting application access "SOME" to ' . $this->appConfig->id . ' for user ' . $user->getName());

		$this->revokeApplicationAllAccessPermission($user);
		$this->revokeApplicationExecuteAccessPermission($user);

		$this->ap->setPermsissionStatus($user, $this->appConfig, ApplicationSomeAccessPermission::NAME, PermissionStatus::ALLOW);
	}
	
	/**
	 * Grants "EXECUTE" access for given user to application. Revokes "ALL" and "SOME" access if granted.
	 * @param User $user
	 * @return boolean 
	 */
	public function grantApplicationExecuteAccessPermission(User $user)
	{
		$this->log->debug('Granting application access "EXECUTE" to ' . $this->appConfig->id . ' for user ' . $user->getName());

		$this->revokeApplicationAllAccessPermission($user);
		$this->revokeApplicationSomeAccessPermission($user);

		$this->ap->setPermsissionStatus($user, $this->appConfig, ApplicationExecuteAccessPermission::NAME, PermissionStatus::ALLOW);
	}

	/**
	 * Revokes "ALL" access for given user to application.
	 * @param User $user
	 */
	public function revokeApplicationAllAccessPermission(User $user)
	{
		$this->log->debug('Revoking application access "ALL" to ' . $this->appConfig->id . ' for user ' . $user->getName());

		$this->ap->setPermsissionStatus($user, $this->appConfig, ApplicationAllAccessPermission::NAME, PermissionStatus::DENY);
	}

	/**
	 * Revokes "SOME" access for given user to application.
	 * @param User $user
	 */
	public function revokeApplicationSomeAccessPermission(User $user)
	{
		$this->log->debug('Revoking appliaction access "SOME" to ' . $this->appConfig->id . ' for user ' . $user->getName());

		$this->ap->setPermsissionStatus($user, $this->appConfig, ApplicationSomeAccessPermission::NAME, PermissionStatus::DENY);
	}

	/**
	 * Revokes "Execute" access for given user to application.
	 * @param User $user
	 */
	public function revokeApplicationExecuteAccessPermission(User $user)
	{
		$this->log->debug('Revoking application access "EXECUTE" to ' . $this->appConfig->id . ' for user ' . $user->getName());

		$this->ap->setPermsissionStatus($user, $this->appConfig, ApplicationExecuteAccessPermission::NAME, PermissionStatus::DENY);
	}

	/**
	 * Returns true if user has "ALL" access to application, false otherwise.
	 * @param User $user
	 * @return boolean
	 */
	public function isApplicationAllAccessGranted(User $user)
	{
		$result = $this->ap->isPermissionGranted($user, $this->appConfig, ApplicationAllAccessPermission::NAME);

		$this->log->debug('Checking for appliaction access "ALL" to ' . $this->appConfig->id . ' for user ' . $user->getName() . ' => ' . ($result ? 'ALLOW' : 'DENY'));

		return $result;
	}

	/**
	 * Returns true if user has "SOME" access to application, false otherwise.
	 * @param User $user
	 * @return boolean
	 */
	public function isApplicationSomeAccessGranted(User $user)
	{
		$result = $this->ap->isPermissionGranted($user, $this->appConfig, ApplicationSomeAccessPermission::NAME);

		$this->log->debug('Checking for appliaction access "SOME" to ' . $this->appConfig->id . ' for user ' . $user->getName() . ' => ' . ($result ? 'ALLOW' : 'DENY'));

		return $result;
	}

	/**
	 * Returns true if user has application access "EXECUTE" granted, false otherwise.
	 * @param User $user
	 * @return boolean
	 */
	public function isApplicationExecuteAccessGranted(User $user)
	{
		$result = $this->ap->isPermissionGranted($user, $this->appConfig, ApplicationExecuteAccessPermission::NAME);

		$this->log->debug('Checking for appliaction access "EXECUTE" to ' . $this->appConfig->id . ' for user ' . $user->getName() . ' => ' . ($result ? 'ALLOW' : 'DENY'));

		return $result;
	}

	/**
	 * Returns true if user has any access granted ("EXECUTE", "SOME", "ALL"), false otherwise.
	 * @param User $user
	 * @return boolean
	 */
	public function isApplicationAnyAccessGranted(User $user)
	{
		return $this->isApplicationAllAccessGranted($user, $this->appConfig) ||
				$this->isApplicationSomeAccessGranted($user, $this->appConfig) ||
				$this->isApplicationExecuteAccessGranted($user, $this->appConfig);
	}

	/**
	 * Returns true if user has admin access granted (SOME, ALL), false otherwise.
	 * @param User $user
	 * @return boolean
	 */
	public function isApplicationAdminAccessGranted(User $user)
	{
		return $this->isApplicationAllAccessGranted($user, $this->appConfig) ||
				$this->isApplicationSomeAccessGranted($user, $this->appConfig);
	}

}
