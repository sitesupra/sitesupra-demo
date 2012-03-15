<?php

namespace Project\SampleAuthentication;

use Supra\Authentication;

/**
 * Authentication PreFilter
 *
 * @author Dmitry Polovka <dmitry.polovka@videinfra.com>
 */
class SampleAuthenticationPreFilter extends Authentication\AuthenticationController
{
	public function __construct()
	{
		$this->setLoginPath(namespace\CONTROLLER_URL . '/login');
		$this->setBasePath(namespace\CONTROLLER_URL);
		$this->setLoginField('supra_login');
		$this->setPasswordField('supra_password');
		
		parent::__construct();
	}
}
