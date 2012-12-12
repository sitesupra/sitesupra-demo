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
		
		$this->getResponse()
				->assign('managerAction', 'MyPassword')
				->assign('manager', $manager)
				->outputTemplate('login/index.html.twig');
		
	}
	
	public function changeAction()
	{
		$user = $this->getUser();
		
		if ( ! $user instanceof Entity\User || $user instanceof Entity\AnonymousUser) {	
			throw new CmsException('Wrong current user!');
		}
		
		$userProvider = ObjectRepository::getUserProvider($this);
		
		$request = $this->getRequest();
		
		// current password
		$plainCurrentPassword = $request->getPostValue('supra_password_current', null);
		$currentPassword = new AuthenticationPassword($plainCurrentPassword);
		
		$authAdapter = $userProvider->getAuthAdapter();
		
		$currentPasswordError = false;
		$newPasswordError = false;
		
		try {
			$authAdapter->authenticate($user, $currentPassword);
		} catch (\Supra\Authentication\Exception\AuthenticationFailure $e) {
			$errorMessage = 'Current password you have entered is invalid';	
			$currentPasswordError = true;
		}
		
		if (is_null($errorMessage)) {
			$newPlainPassword = $request->getPostValue('supra_password', null);
			$confirmPasswordPlain = $request->getPostValue('supra_password_confirm', null);

			if ($newPlainPassword != $confirmPasswordPlain) {
				throw new CmsException('Confirmation password does not match the password');
			}

			$newPassword = new AuthenticationPassword($newPlainPassword);

			try {
				
				$oldPasswordHash = $user->getPassword();
				$oldPasswordSalt = $user->getSalt();
				
				$userProvider->credentialChange($user, $newPassword);
				
				$passwordRecord = new \Supra\Password\Entity\PasswordHistoryRecord();
				
				$passwordRecord->setHash($oldPasswordHash);
				$passwordRecord->setSalt($oldPasswordSalt);
				
				$passwordRecord->setUser($user);
				
				$em = ObjectRepository::getEntityManager($this);
				$em->persist($passwordRecord);
				
				$em->flush();
				
				$userProvider->updateUser($user);
				
			} catch (\Supra\Password\Exception\PasswordPolicyException $e) {
				$errorMessage = $e->getMessage();
				$newPasswordError = true;
			}
		}

		if ( ! empty($errorMessage)) {
			$success = false;
		} else {
			$success = true;
		}
		
		$this->getResponse()
				->setResponseData(array(
					'success' => $success, 
					'errorMessage' => $errorMessage,
					'errorFields' => array(
						'passwordNew' => $newPasswordError,
						'passwordCurrent' => $currentPasswordError,
					)
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
