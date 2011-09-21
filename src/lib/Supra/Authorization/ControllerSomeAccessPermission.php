<?php

namespace Supra\Authorization;

use Symfony\Component\Security\Acl\Permission\MaskBuilder;

class ControllerSomeAccessPermission extends PermissionType 
{
	function __construct() 
	{
		parent::__construct('controller_some', MaskBuilder::MASK_OWNER >> 2);
	}
}
