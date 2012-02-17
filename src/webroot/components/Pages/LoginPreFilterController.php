<?php

namespace Project\Pages;

use Supra\Controller;
use Supra\Controller\Exception;
use Supra\Request;
use Supra\Response;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Uri\Path;
use Supra\Authentication;


class LoginPreFilterController extends Authentication\AuthenticationController
{

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
	 * @param string $publicUrl
	 * @return boolean
	 */
	protected function isPublicUrl($publicUrl)
	{
		return true;
	}

}