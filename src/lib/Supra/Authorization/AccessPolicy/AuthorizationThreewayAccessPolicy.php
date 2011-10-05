<?php

namespace Supra\Authorization\AccessPolicy;

use Supra\User\Entity\Abstraction\User;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\Permission\PermissionStatus;

abstract class AuthorizationThreewayAccessPolicy extends AuthorizationAccessPolicyAbstraction
{
	/**
	 * @var string
	 */
	private $subpropertyClass;
	
	/**
	 * @var array
	 */
	private $subpropertyPermissionNames;
	
	function __construct($subpropertyLabel, $subpropertyClass) 
	{
		parent::__construct();
		
		$subpropertyClass::registerPermissions($this->ap);
		
		$this->subpropertyPermissionNames = array_keys($this->ap->getPermissionsForClass($subpropertyClass));
		
		$subpropertyValues = array();
		foreach ($this->subpropertyPermissionNames as $name) {
			$subpropertyValues[] = array('id' => $name, 'title' => "{#userpermissions.label_" . $name . "#}");
		}
		
		$this->subpropertyClass = $subpropertyClass;
		
		$this->permission = array(
			"id" => self::PERMISSION_NAME,
			"type" => "Dial",
			"label" => "{#userpermissions.label_persmissions#}",
			"values" => array(
				array("id" => "2", "title" => "{#userpermissions.label_none#}"),
				array("id" => "1", "title" => "{#userpermissions.label_some#}"),
				array("id" => "0", "title" => "{#userpermissions.label_all#}")
			),
			"value" => "0",
			"sublabel" => $subpropertyLabel,
			"subproperty" => array(
				"id" => "permissions",
				"type" => "SelectList",
				"multiple" => true,
				"values" => $subpropertyValues
			)
		);
	}
	
	function getAccessPermission(User $user) 
	{
		$result = "2";
		
		if($this->ap->isApplicationAllAccessGranted($user, $this->getAppConfig())) {
			$result = "0";
		}
		else if($this->ap->isApplicationSomeAccessGranted($user, $this->getAppConfig())) {
			$result = "1";
		}
		
		return $result;
	}
	
	function setAccessPermission(User $user, $value) 
	{
		if($value == "0") {
			$this->ap->grantApplicationAllAccessPermission($user, $this->getAppConfig());
		}
		else if($value == "1") {
			$this->ap->grantApplicationSomeAccessPermission($user, $this->getAppConfig());
		}
		else {
			$this->ap->grantApplicationExecuteAccessPermission($user, $this->getAppConfig());
		}
	}	
		
	public function getItemPermissions(User $user)
	{
		$permissionsForClass = $this->ap->getEffectivePermissionStatusesByObjectClass($user, $this->subpropertyClass);
	
		$result = array();
		foreach($permissionsForClass as $itemId => $permissionsStatus) {
			
			$permissions = array();
			foreach($permissionsStatus as $permissionName => $permissionStatus) {
				
				if($permissionStatus == PermissionStatus::ALLOW) {
					$permissions[] = $permissionName;
				}
			}
			
			if( ! empty($permissions)) {
				$itemPermission = $this->getItemPermission($user, $itemId, $permissions);
				
				if ( ! empty($itemPermission)) {
					$result[] = $itemPermission;
				}
			}
		}
		
		\Log::debug('HAVE PERMISSIONS FOR ITEMS: ', array_keys($permissionsForClass));
		
		return $result;
	}
	
	protected function getItemPermission(User $user, $itemId, $permissions) 
	{
		return array('id' => $itemId, 'value' => $permissions);
	}
	
	public function setItemPermissions(User $user, $itemId, $setPermissionNames) 
	{
		$oid = $this->ap->createObjectIdentity($itemId, $this->subpropertyClass);
		
		foreach($this->subpropertyPermissionNames as $permissionName) {
			
			if(in_array($permissionName, $setPermissionNames)) {
				$permissionStatus = PermissionStatus::ALLOW;
			}
			else {
				$permissionStatus = PermissionStatus::DENY;
			}

			$this->ap->setPermsission($user, $oid, $permissionName, $permissionStatus);
		}
	}
}
