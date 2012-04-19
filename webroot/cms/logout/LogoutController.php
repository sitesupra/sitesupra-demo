<?php

namespace Supra\Cms\Logout;

use Supra\Controller\SimpleController;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Log\Log;
use Supra\User\Entity\AbstractUser;

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
		$user = $userProvider->getSignedInUser();

		if ($user instanceof AbstractUser) {
			$userProvider->signOut();
			$auditLog = ObjectRepository::getAuditLogger($this);
			$auditLog->info(null, 'login', "User '{$user->getEmail()}' logged out", $user);
		}
		
		$loginPage = $this->getLoginPage();
		$this->response->redirect($loginPage);
	}
}