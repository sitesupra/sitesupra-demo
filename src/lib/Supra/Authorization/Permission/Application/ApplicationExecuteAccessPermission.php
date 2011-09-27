<?php

namespace Supra\Authorization\Permission\Application;

use Supra\Authorization\Permission\Permission;
use Supra\Authorization\AuthorizationProvider;

class ApplicationExecuteAccessPermission extends Permission
{
	const NAME = 'application_execute_access';
	const MASK = 1024; // ==> MaskBuilder::MASK_OWNER >> 3;
	
	function __construct() 
	{
		parent::__construct(self::NAME, self::MASK, AuthorizationProvider::APPLICATION_CONFIGURATION_CLASS);
	}
}
