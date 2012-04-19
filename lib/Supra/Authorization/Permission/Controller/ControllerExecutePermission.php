<?php

namespace Supra\Authorization\Permission\Controller;

use Supra\Authorization\Permission\Permission;
use Supra\Authorization\AuthorizationProvider;

class ControllerExecutePermission extends Permission
{
	const NAME = 'controller_execute';
	const MASK = 1024; // ==> MaskBuilder::MASK_OWNER >> 3;
	
	function __construct() 
	{
		parent::__construct(self::NAME, self::MASK, AuthorizationProvider::AUTHORIZED_CONTROLLER_INTERFACE);
	}
}
