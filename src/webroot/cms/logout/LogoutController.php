<?php

namespace Supra\Cms\Logout;

use Supra\Controller\SimpleController;
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
	
	private $loginPage = '/cms/login';
	
	public function getLoginPage()
	{
		return $this->loginPage;
	}
	
	public function indexAction()
	{
		$loginPage = $this->getLoginPage();
		
		if(! empty($_SESSION['user'])) {
			unset($_SESSION['user']);
		}
		
		$this->response->redirect($loginPage);
	}
}