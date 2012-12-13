<?php

namespace Supra\Cms\Login\Mypassword;

use Supra\Request;
use Supra\Cms\CmsAction;
use Supra\Cms\Exception\CmsException;
use Supra\ObjectRepository\ObjectRepository;

use Supra\User\Entity;
use Supra\Authentication\AuthenticationPassword;

/**
 *
 */
class MypasswordAction extends CmsAction
{
	/**
	 * 
	 */
	public function indexAction()
	{
		$manager = new \Supra\Cms\ApplicationConfiguration();
		$manager->id = 'login';
		$manager->title = 'Change your password';
		$manager->url = 'login/mypassword';
		$manager->configure();
		
		$passwordRequirements = array();
		$userProvider = ObjectRepository::getUserProvider($this);
		
		$passwordPolicy = $userProvider->getPasswordPolicy();
		if ( ! is_null($passwordPolicy)) {
			$filters = $passwordPolicy->getValidationFilters();
			foreach($filters as $filter) {
				/* @var $filter Supra\Password\Validation\PasswordValidationInterface */
				$passwordRequirements[] = $filter->getFilterRequirements();
			}
		}
		
		$this->getResponse()
				->assign('passwordRequirements', $passwordRequirements)
				->assign('managerAction', 'MyPassword')
				->assign('manager', $manager)
				->outputTemplate('login/index.html.twig');
		
	}
	
	public function changeAction()
	{
		$user = $this->getUser();
		
		if ( ! $user instanceof Entity\User || $user instanceof Entity\AnonymousUser) {	
			throw new CmsException('Wrong current user object');
		}
		
		$userProvider = ObjectRepository::getUserProvider($this);
		
		$request = $this->getRequest();
		
		// current password
		$plainCurrentPassword = $request->getPostValue('supra_password_current', null);
		$currentPassword = new AuthenticationPassword($plainCurrentPassword);
		
		$authAdapter = $userProvider->getAuthAdapter();
		
		$currentPasswordError = false;
		$newPasswordError = false;
		$success = false;
		
		try {
			$authAdapter->authenticate($user, $currentPassword);
		} catch (\Supra\Authentication\Exception\AuthenticationFailure $e) {
			$currentPasswordError = true;
		}
		
		$newPlainPassword = $request->getPostValue('supra_password', null);
		$confirmPasswordPlain = $request->getPostValue('supra_password_confirm', null);

		if ($newPlainPassword != $confirmPasswordPlain) {
			throw new CmsException('Confirmation password does not match the password');
		}

		$newPassword = new AuthenticationPassword($newPlainPassword);
		
		try {
			$userProvider->validateUserPassword($newPassword, $user);
		} catch (\Supra\Password\Exception\PasswordPolicyException $e) {
			$newPasswordError = true;
		}

		if ( ! $currentPasswordError && ! $newPasswordError) {
			$userProvider->credentialChange($user, $newPassword);
			$userProvider->updateUser($user);
			
			$success = true;
		}
		
		$this->getResponse()
				->setResponseData(array(
					'success' => $success,
					
					'errors' => array(
						'password_new' => $newPasswordError,
						'password_current' => $currentPasswordError,
					),
		));
	}
	
	/**
	 * 
	 * @param \Supra\Request\RequestInterface $request
	 * @return Supra\Response\TwigResponse
	 */
	public function createResponse(Request\RequestInterface $request)
	{	
		if ($request->isPost()) {
			return new \Supra\Response\JsonResponse();
		}
		
		return $this->createTwigResponse();
	}
	
}
