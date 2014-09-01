<?php

namespace Project\Blocks\Login;

use Supra\Controller;
use Supra\Controller\Exception;
use Supra\Request;
use Supra\Response;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Uri\Path;
use Supra\Authentication;

/**
 * Authentication data POST fetch prefilter
 */
class LoginPreFilterController extends Authentication\AuthenticationController
{
	/**
	 * Configurate the prefilter
	 */
	public function __construct()
	{
		// in case of failure/success - skip user redirecting
		$this->setSkipRedirect(true);
		
		$this->setLoginField('login');
		$this->setPasswordField('password');
		
		parent::__construct();
	}
	
	/**
	 * Override parent method, to turn off redirect for all cases
	 * @param Path $path
	 * @return boolean
	 */
	protected function isPublicUrl(Path $path)
	{
		return true;
	}
}
