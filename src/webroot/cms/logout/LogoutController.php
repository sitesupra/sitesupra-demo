<?php

namespace Supra\Cms\Logout;

use Supra\Controller\SimpleController;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Log\Log;

/**
 * Logout controller
 */
class LogoutController extends SimpleController
{
	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected $defaultAction = 'index';
	
	/**
	 * Login page path
	 * @var string
	 */
	//TODO: Move configuration to Configuration object
	private $loginPage = '/cms/login';
	
	public function getLoginPage()
	{
		return $this->loginPage;
	}
	
	/**
	 * Signs out the user
	 */
	public function indexAction()
	{
		$userProvider = ObjectRepository::getUserProvider($this);
		$userProvider->signOut();
		
		$loginPage = $this->getLoginPage();
		$this->response->redirect($loginPage);
	}
}