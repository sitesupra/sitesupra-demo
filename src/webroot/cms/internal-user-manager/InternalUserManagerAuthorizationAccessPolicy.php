<?php

namespace Supra\Cms\InternalUserManager;

use Supra\Authorization\AccessPolicy\AuthorizationAllOrNoneAccessPolicy;
use Supra\Authorization\AccessPolicy\AuthorizationThreewayAccessPolicy;

//class InternalUserManagerAuthorizationAccessPolicy extends AuthorizationAllOrNoneAccessPolicy 
class InternalUserManagerAuthorizationAccessPolicy extends AuthorizationThreewayAccessPolicy
{

}

