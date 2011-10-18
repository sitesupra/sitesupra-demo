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
	
	public function indexAction()
	{
		$session = ObjectRepository::getSessionManager($this)
				->getAuthenticationSpace();
		
		$loginPage = $this->getLoginPage();
		
		$user = $session->getUser();
		
		if ( ! empty($user)) {
			$session->removeUser();
		}
		
		$this->response->redirect($loginPage);
	}
}