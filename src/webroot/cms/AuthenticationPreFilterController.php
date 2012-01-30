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
		$this->setLoginPath('cms/login');
		$this->setBasePath('cms');
		$this->setLoginField('supra_login');
		$this->setPasswordField('supra_password');
		
		$this->publicUrlList = array(
			'cms/internal-user-manager/restore',
			'cms/logout'
		);
		
		parent::__construct();
	}
	
}
