<?php

namespace Supra\Authorization\AccessPolicy;

use Supra\ObjectRepository\ObjectRepository;
use Supra\User\Entity\Abstraction\User;

abstract class AuthorizationAllOrNoneAccessPolicy extends AuthorizationAccessPolicyAbstraction
{
	function __construct() 
	{
		parent::__construct();
		
		$this->permission = array(
			"id" => self::PERMISSION_NAME,
			"type" => "SelectList",
			"label" => "{#userpermissions.label_persmissions#}",
			"values" => array(
				array("id" => "2", "title" => "{#userpermissions.label_none#}"),
				array("id" => "0", "title" => "{#userpermissions.label_all#}")
			),
			"value" => "0"
		);
	}
	
	function setAccessPermission(User $user, $value) 
	{
		if($value == "0") {
			$this->ap->revokeApplicationExecutePermission($user, $this->getAppConfig());
			$this->ap->grantApplicationAllAccessPermission($user, $this->getAppConfig());
		}
		else {
			$this->ap->revokeApplicationAllAccessPermission($user, $this->getAppConfig());
			$this->ap->grantApplicationExecuteAccessPermission($user, $this->getAppConfig());
		}
	}
	
	function getAccessPermission(User $user) 
	{
		$result = "2";
		
		if($this->ap->isApplicationAllAccessGranted($user, $this->getAppConfig())) {
			$result = "0";
		}
		
		return $result;
	}
}
