<?php

namespace Supra\Authorization\AccessPolicy;

use Supra\ObjectRepository\ObjectRepository;

class AuthorizationAllOrNoneAccessPolicy extends AuthorizationAccessPolicyAbstraction
{
	function __construct() 
	{
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
	
	public function isVisibleForUser(User $user) 
	{
		$ap = ObjectRepository::getAuthorizationProvider($this->applicationConfiguration->applicationNamespace);
		return $ap->isControllerAnyAccessGranted($user, $this->applicationConfiguration->applicationNamespace);
	}
}
