<?php

namespace Supra\Authorization\Permission\Application;

use Supra\Authorization\Permission\Permission;
use Supra\Authorization\AuthorizationProvider;

class ApplicationSomeAccessPermission extends Permission 
{
	const NAME = 'application_some_access';
	const MASK = 512; // ==> MaskBuilder::MASK_OWNER >> 2
	
	function __construct() 
	{
		parent::__construct(self::NAME, self::MASK, AuthorizationProvider::APPLICATION_CONFIGURATION_CLASS);
	}
}
