<?php

namespace Supra\Cms\InternalUserManager\Restore;

use Supra\Cms\CmsAction;
use Supra\Controller\SimpleController;
use Supra\Response\TwigResponse;
use Supra\Request;
use Supra\Cms\InternalUserManager\InternalUserManagerAbstractAction;
use Supra\Controller\Exception;
use Supra\Exception\LocalizedException;
use Supra\ObjectRepository\ObjectRepository;
use Supra\User\Entity\User;
use Supra\Authentication\AuthenticationPassword;

/**
 * Restore password
 *
 * @method TwigResponse getResponse()
 * @author Dmitry Polovka <dmitry.polovka@videinfra.com>
 */
class RestoreAction extends InternalUserManagerAbstractAction
{
	/**
	 * Minimum password length
	 */
	const MIN_PASSWORD_LENGTH = 4;
	
	/**
	 * Login page path
	 */
	const LOGIN_PAGE = '/cms/login';
	
	/**
	 * Overwriting JsonResponse to TwigResponse
	 * @param Request\RequestInterface $request
	 * @return TwigResponse 
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		$response = new TwigResponse($this);

		return $response;
	}
	
	/**
	 * Validates hash
	 * @param type $expirationTime
	 * @param type $salt
	 * @param type $email
	 * @param type $hash
	 * @return boolean
	 */
	private function validateHash($expirationTime, $salt, $email, $hash)
	{
		$generatedHash = sha1($expirationTime . $salt . $email);

		if ($generatedHash == $hash) {
			return true;
		}

		return false;
	}

	/**
	 * Restore index action which make check and then renders form
	 * @return type 
	 */
	public function indexAction()
	{
		/* @var $repo HttpResponse */
		$response = $this->getResponse();
		if (($this->emptyRequestParameter('e')) || ($this->emptyRequestParameter('t')) || ($this->emptyRequestParameter('h'))) {
			$response->output('Wrong parameters passed');
			return;
		}
		
		$email = $this->getRequestParameter('e');
		$expirationTime = $this->getRequestParameter('t');
		$hash = $this->getRequestParameter('h');
		
		$user = $this->validateUser();

		if ($user instanceof User) {
			
			$response->assign('email', $email);
			$response->assign('time', $expirationTime);
			$response->assign('hash', $hash);
			
			$response->outputTemplate('form.html.twig');
			return;
		} else {
			$response->output('Wrong link. Try to request a new one');
			return;
		}
	}

	/**
	 * Actual change password action
	 * @return type 
	 */
	public function changepasswordAction()
	{
		$this->isPostRequest();
		
		$plainPassword = $this->getRequestParameter('password');
		$confirmPassword = $this->getRequestParameter('confirm_password');

		// Check password match
		if($plainPassword != $confirmPassword) {
			$this->getResponse()->output('Passwords do not match');
			return;
		}
		
		$passwordLength = strlen($plainPassword);
		$password = new AuthenticationPassword($plainPassword);
		
		// check password lenght
		if($passwordLength < self::MIN_PASSWORD_LENGTH) {
			$this->getResponse()->output('Passwords length should be '. self::MIN_PASSWORD_LENGTH .' or more characters');
			return;
		}
		
		$user = $this->validateUser();
		
		if (is_null($user)) {
			$this->getResponse()->output('Something went wrong. Try to request new link.');
			return;
		}
		
		$salt = $user->resetSalt();
		
		$userProvider = ObjectRepository::getUserProvider($this);
		
		$authAdapter = $userProvider->getAuthAdapter();
		$authAdapter->credentialChange($user, $password);
		
		$this->entityManager->flush();
		
		$this->getResponse()->redirect(self::LOGIN_PAGE);
				
	}
	
	public function execute()
	{
		// Handle localized exceptions
		try {
			parent::execute();
		} catch (LocalizedException $exception) {
			
			// No support for not Json actions
			$response = $this->getResponse();
			if ( ! $response instanceof HttpResponse) {
				throw $exception;
			}
			
			//TODO: should use exception "message" at all?
			$message = $exception->getMessage();
			$messageKey = $exception->getMessageKey();
			
			if ( ! empty($messageKey)) {
				$message = '{#' . $messageKey . '#}';
				$response->output($messageKey);
			}

			$response->output($message);
		} catch (\Exception $e) {
			$response = $this->getResponse();
			if ( ! $response instanceof HttpResponse) {
				throw $e;
			}
			
			//TODO: Remove later. Should not be shown to user
			$response->output($e->getMessage());
		}
	}
	
	/**
	 * @return User
	 */
	private function validateUser()
	{	
		$email = $this->getRequestParameter('e');
		$expirationTime = $this->getRequestParameter('t');
		$hash = $this->getRequestParameter('h');
		
		$repo = $this->entityManager->getRepository('Supra\User\Entity\User');
		//TODO: should it search by email or login?
		$user = $repo->findOneByEmail($email);

		// find user
		if (empty($user)) {
			return null;
		}
		
		$currentSalt = $user->getSalt();
		$result = $this->validateHash($expirationTime, $currentSalt, $email, $hash);
		
		if ( ! $result) {
			return null;
		}
		
		return $user;
	}
}
