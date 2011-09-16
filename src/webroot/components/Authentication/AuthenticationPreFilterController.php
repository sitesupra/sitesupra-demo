<?php

namespace Project\Authentication;

use Supra\Controller\Authentication;
/**
 * Authentication PreFilter
 *
 * @author Dmitry Polovka <dmitry.polovka@videinfra.com>
 */
class AuthenticationPreFilterController extends Authentication\AuthenticationController
{
	public function __construct()
	{
		$this->setLoginPath('cms/login');
		$this->setBasePath('cms');
		$this->setLoginField('supra_login');
		$this->setPasswordField('supra_password');
		
		parent::__construct();
	}
	
}
