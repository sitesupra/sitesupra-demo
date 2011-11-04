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
use Supra\Uri\Path;

/**
 * Restore password controller
 *
 * @method TwigResponse getResponse()
 * @author Dmitry Polovka <dmitry.polovka@videinfra.com>
 */
class RestoreController extends InternalUserManagerAbstractAction
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
	 * @return string
	 */
	protected function getBasePath()
	{
		$request = $this->getRequest();
		
		$basePath = $request->getPath()
					->getBasePath(0, Path::FORMAT_BOTH_DELIMITERS);
		
		return $basePath;
	}
	
	/**
	 * Restore index action which make check and then renders form
	 */
	public function indexAction()
	{
		$request = $this->getRequest();
		/* @var $request Request\HttpRequest */
		$response = $this->getResponse();
		
		$basePath = $this->getBasePath();
		$response->assign('basePath', $basePath);
		
		if (($this->emptyRequestParameter('e')) || ($this->emptyRequestParameter('t')) || ($this->emptyRequestParameter('h'))) {
			
			
			$response->redirect($basePath . 'request');
			return;
		}
		
		$email = $this->getRequestParameter('e');
		$time = $this->getRequestParameter('t');
		$hash = $this->getRequestParameter('h');
		
		$user = $this->validateUser();

		if ($user instanceof User) {
			
			$response->assign('email', $email);
			$response->assign('time', $time);
			$response->assign('hash', $hash);
			
			$response->outputTemplate('form.html.twig');
			return;
		} else {
			$response->output('Wrong link. Try to request a new one');
			return;
		}
	}
	
	/**
	 * User requests new password
	 */
	public function requestAction()
	{
		$request = $this->getRequest();
		$response = $this->getResponse();
		$email = '';
		$errorMessage = '';
		
		if ($request->isPost()) {
			$email = $request->getPostValue('email');
			
			if (empty($email)) {
				$errorMessage = 'No email address passed';
				
			} elseif ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$errorMessage = 'Email address not valid';
			
			} else {

				$user = $this->getRequestedUser($email);
				
				if ( ! $user instanceof User) {
					$errorMessage = 'User with such email address is not found';
				} else {

					$this->sendPasswordChangeLink($user);
					
					$response->outputTemplate('request.success.html.twig');
					return;
				}
			}
		}
		
		$response->assign('email', $email);
		$response->assign('errorMessage', $errorMessage);
		
		$response->outputTemplate('request.html.twig');
	}
	
	/**
	 * Actual change password action
	 * @return type 
	 */
	public function changepasswordAction()
	{
		$this->isPostRequest();
		
		$response = $this->getResponse();
		
		// Assign parameters back to template
		$email = $this->getRequestParameter('e');
		$time = $this->getRequestParameter('t');
		$hash = $this->getRequestParameter('h');
		
		$response->assign('email', $email);
		$response->assign('time', $time);
		$response->assign('hash', $hash);
		
		$plainPassword = $this->getRequestParameter('password');
		$confirmPassword = $this->getRequestParameter('confirm_password');

		// Check password match
		if($plainPassword !== $confirmPassword) {
			$response->assign('errorMessage', 'Passwords do not match');
			$response->outputTemplate('form.html.twig');
			
			return;
		}
		
		// Don't need anymore
		unset($confirmPassword);
		
		$passwordLength = strlen($plainPassword);
		$password = new AuthenticationPassword($plainPassword);
		
		// TODO: password policy should be configurable for user provider
		// check password lenght
		if($passwordLength < self::MIN_PASSWORD_LENGTH) {
			$response->assign('errorMessage', 'Passwords length should be '. self::MIN_PASSWORD_LENGTH .' or more characters');
			$response->outputTemplate('form.html.twig');
			
			return;
		}
		
		$user = $this->validateUser();
		
		if (is_null($user)) {
			$response->output('errorMessage', 'Something went wrong. Try to request new link.');
			
			return;
		}
		
		$salt = $user->resetSalt();
		
		$userProvider = ObjectRepository::getUserProvider($this);
		$authAdapter = $userProvider->getAuthAdapter();
		
		$authAdapter->credentialChange($user, $password);
		
		$this->entityManager->flush();
		
		$response->redirect(self::LOGIN_PAGE);
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
		
		$user = $this->getRequestedUser($email);

		// find user
		if (empty($user)) {
			return null;
		}
		
		$currentSalt = $user->getSalt();
		$time = $this->getRequestParameter('t');
		$hash = $this->getRequestParameter('h');
		
		$result = $this->validateHash($user, $time, $hash);
		
		if ( ! $result) {
			return null;
		}
		
		return $user;
	}
	
	/**
	 * @param string $email
	 * @return User
	 */
	private function getRequestedUser($email)
	{
		$repo = $this->entityManager->getRepository(User::CN());
		//TODO: should it search by email or login?
		$user = $repo->findOneByEmail($email);
		
		return $user;
	}
	
	/**
	 * Validates hash
	 * @param string User $user
	 * @param string $time
	 * @param string $hash
	 * @return boolean
	 */
	private function validateHash(User $user, $time, $hash)
	{
		// Request valid 24h
		if (strtotime('+1 day', $time) < time()) {
			return false;
		}
		
		$generatedHash = $this->generatePasswordRecoveryHash($user, $time);

		if ($generatedHash === $hash) {
			return true;
		}

		return false;
	}
}
