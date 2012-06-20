<?php

namespace Supra\Cms\BannerManager;

use Supra\Authorization\AccessPolicy\AuthorizationAllOrNoneAccessPolicy;

class BannerManagerAuthorizationAccessPolicy extends AuthorizationAllOrNoneAccessPolicy
{

	protected function setApplicationAccessValue(AbstractUser $user, $applicationAccessValue)
	{
		if($applicationAccessValue == self::APPLICATION_ACCESS_NONE_VALUE) {
			
			$this->revokeApplicationAllAccessPermission($user);
			$this->revokeApplicationSomeAccessPermission($user);
			$this->revokeApplicationExecuteAccessPermission($user);
		}
		else {
			
			parent::setApplicationAccessValue($user, $applicationAccessValue);
		}
	}

}

