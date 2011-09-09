<?php
namespace Project\Authenticate;

use Supra\Controller\Authentication;
/**
 * Authentication PreFilter
 *
 * @author Dmitry Polovka <dmitry.polovka@videinfra.com>
 */
class AuthenticatePreFilterController extends Authentication\AuthenticationController
{
	public function __construct()
	{
		$this->setLoginPath('/authenticate/login');
		$this->setCmsPath('/cms');
		$this->setLoginField('supra_login');
		$this->setPasswordField('supra_password');
		
		parent::__construct();
	}
	
}