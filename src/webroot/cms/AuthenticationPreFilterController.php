<?php

namespace Supra\Cms;

use Supra\Authentication\AuthenticationController;

/**
 * Authentication PreFilter
 *
 * @author Dmitry Polovka <dmitry.polovka@videinfra.com>
 */
class AuthenticationPreFilterController extends AuthenticationController
{
	public function __construct()
	{
		$cmsBase = SUPRA_CMS_URL;

		$this->setLoginPath($cmsBase . '/login/');
		$this->setBasePath($cmsBase . '/');
		$this->setLoginField('supra_login');
		$this->setPasswordField('supra_password');
		
		$this->publicUrlList = array(
			$cmsBase . '/restore',
			$cmsBase . '/restore/changepassword',
			$cmsBase . '/restore/request',
			$cmsBase . '/restore',
			$cmsBase . '/logout'
		);
		
		parent::__construct();
	}
	
}
