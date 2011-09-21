<?php

namespace Supra\Authorization;

use Symfony\Component\Security\Acl\Permission\MaskBuilder;

class ControllerAllAccessPermission extends PermissionType 
{
	function __construct() 
	{
		parent::__construct('controller_all', MaskBuilder::MASK_OWNER >> 1);
	}
}
