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
	protected static $defaultAction = 'index';
	
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
	
	public function indexAction()
	{
		$session = ObjectRepository::getSessionNamespace($this);
		
		$loginPage = $this->getLoginPage();
		
		if(! empty($session->user)) {
			unset($session->user);
		}
		
		$this->response->redirect($loginPage);
	}
}