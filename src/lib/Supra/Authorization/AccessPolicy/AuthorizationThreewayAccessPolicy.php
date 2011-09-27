<?php

namespace Supra\Authorization\AccessPolicy;

class AuthorizationThreewayAccessPolicy extends AuthorizationAccessPolicyAbstraction
{
	function __construct($sublabel, $subPermissionNames) 
	{
		$subpropertyValues = array();
		foreach ($subPermissionNames as $name) {
			$subpropertyValues[] = array('id' => $name, 'title' => "{#userpermissions.label_" . $name . "#}");
		}
		
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
			"sublabel" => $sublabel,
			"subproperty" => array(
				"id" => "permissions",
				"type" => "SelectList",
				"multiple" => true,
				"values" => $subpropertyValues
			)
		);
	}
}
