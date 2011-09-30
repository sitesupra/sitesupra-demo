<?php

namespace Supra\Cms\ContentManager;

use Supra\Authorization\AccessPolicy\AuthorizationThreewayAccessPolicy;
use Supra\Controller\Pages\Entity\Abstraction\Entity;

class ContentManagerAuthorizationAccessPolicy extends AuthorizationThreewayAccessPolicy {
	
	function __construct() 
	{
		parent::__construct('pages', Entity::CN());
	}
}
