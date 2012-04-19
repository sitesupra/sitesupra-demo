<?php

namespace Supra\Authorization\Permission\Application;

use Supra\Authorization\Permission\Permission;
use Supra\Authorization\AuthorizationProvider;

class ApplicationAllAccessPermission extends Permission 
{
	const NAME = 'application_all_access';
	const MASK = 256; // ==> MaskBuilder::MASK_OWNER >> 1	
	
	function __construct() 
	{
		parent::__construct(self::NAME, self::MASK, AuthorizationProvider::APPLICATION_CONFIGURATION_CLASS);
	}
}
