<?php

namespace Supra\Authentication;

use Supra\Controller\ControllerAbstraction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller;
use Supra\Controller\Exception\StopRequestException;
use Supra\Request;
use Supra\Response;
use Supra\User;
use Supra\Uri\Path;

/**
 * Authentication controller
 */
abstract class AuthenticationController extends ControllerAbstraction implements Controller\PreFilterInterface
{
	/**
	 * Login page path
	 * @var string
	 */
	protected $loginPath = 'login';

	/**
	 * Base path
	 * @var string
	 */
	protected $basePath = '/';

	/**
	 * Login field name on login page
	 * @var string
	 */
	protected $loginField = 'login';

	/**
	 * Password field name on login page
	 * @var string
	 */
	protected $passwordField = 'password';

	/**
	 * Public url list in restricted area
	 * @var array
	 */
	public $publicUrlList = array();

	/**
	 * Public URL list
	 * @return array
	 */
	public function getPublicUrlList()
	{
		return $this->publicUrlList;
	}

	/**
	 * Returns login field name
	 * @return string
	 */
	public function getLoginField()
	{
		return $this->loginField;
	}

	/**
	 * Sets login field name
	 * @param string $loginField 
	 */
	public function setLoginField($loginField)
	{
		$this->loginField = $loginField;
	}

	/**
	 * Returns password field name
	 * @return string 
	 */
	public function getPasswordField()
	{
		return $this->passwordField;
	}

	/**
	 * Sets password field name
	 * @param string $passwordField 
	 */
	public function setPasswordField($passwordField)
	{
		$this->passwordField = $passwordField;
	}

	/**
	 * Returns login page path
	 * @return string 
	 */
	public function getLoginPath()
	{
		return trim($this->loginPath, '/');
	}

	/**
	 * Sets login page path
	 * @param string $loginPath 
	 */
	public function setLoginPath($loginPath)
	{
		$this->loginPath = $loginPath;
	}

	/**
	 * Returns base path
	 * @return string 
	 */
	public function getBasePath()
	{
		return '/' . trim($this->basePath, '/');
	}

	/**
	 * Sets base path
	 * @param string $basePath 
	 */
	public function setBasePath($basePath)
	{
		$this->basePath = $basePath;
	}

	/**
	 * Main executable action
	 */
	public function execute()
	{
		$sessionManager = ObjectRepository::getSessionManager($this);
		$session = $sessionManager->getAuthenticationSpace();
		/* @var $session Supra\Authentication\AuthenticationSessionNamespace */
		
		// Unset the session data
		unset($session->login, $session->message);
		
		$request = $this->getRequest();
		$isPublicUrl = $this->isPublicUrl($request->getRequestUri());

		// Allow accessign public URL
		if ($isPublicUrl) {
			return;
		}

		$xmlHttpRequest = false;
		$requestedWith = $this->getRequest()->getServerValue('HTTP_X_REQUESTED_WITH');

		if ($requestedWith == 'XMLHttpRequest') {
			$xmlHttpRequest = true;
		}

		$post = $this->getRequest()->isPost();

		// if post request then check for login and password fields presence
		if ($post) {

			// login and password fields name
			$loginField = $this->getLoginField();
			$passwordField = $this->getPasswordField();

			// login and password
			$login = $this->getRequest()->getPostValue($loginField);
			$plainPassword = $this->getRequest()->getPostValue($passwordField);
			$password = new AuthenticationPassword($plainPassword);

			if ( ! empty($login)) {
				
				// Authenticating user
				$user = null;
				try {
					
					// TODO: Maybe should be moved to some password policy guide with password expire features?
					if ($password->isEmpty()) {
						throw new Exception\WrongPasswordException("Empty passwords are not allowed");
					}
					
					$userProvider = ObjectRepository::getUserProvider($this);
					$user = $userProvider->authenticate($login, $password);
					
					// TODO: user provider should have session storage instead
//					$userProvider->startSession();
					
				} catch (Exception\AuthenticationFailure $exc) {
					//TODO: pass the failure message somehow
				}

				if ( ! empty($user)) {

					$uri = $this->getSuccessRedirectUrl();

					$session->setUser($user);

					if ($xmlHttpRequest) {
						$this->response->setCode(200);
					} else {
						$this->response->redirect($uri);
					}

					throw new StopRequestException("Login success");
				} else {
					$message = 'Incorrect login name or password';

					if ($xmlHttpRequest) {
						$this->response->setCode(401);
						$this->response->header('X-Authentication-Pre-Filter-Message', $message);
					} else {

						$session->login = $login;
						$session->message = $message;
						
						$request = $this->request;
						
						/* @var $request Request\HttpRequest */
						
						// if authentication failed, we redirect user to login page
						$loginPath = $this->getLoginPath();
						$path = new Path($loginPath);
						$request->setPath($path);
						
						return;
					}
					
					throw new StopRequestException("Login failure");
				}
			}
		}

		// check for session presence
		$sessionUser = $session->getUser();

		// if session is empty we redirect user to login page
		if (empty($sessionUser)) {

			$loginPath = $this->getLoginPath();
			$uri = $this->getRequest()->getActionString();

			if ($uri != $loginPath) {

				if ($xmlHttpRequest) {
					$this->response->setCode(401);
				} else {
					$fullUri = $this->getRequest()->getRequestUri();
					$this->response->redirect('/' . $loginPath . '?redirect_to=' . urlencode($fullUri));
				}

				throw new StopRequestException("User not authenticated");
			}
		} else {
			$loginPath = $this->getLoginPath();
			$uri = $this->getRequest()->getActionString();

			// Redirect from login form if the session is active
			if ($uri == $loginPath) {
				$uri = $this->getSuccessRedirectUrl();
				$this->response->redirect($uri);

				throw new StopRequestException("Session is already active");
			}
		}
	}

	/**
	 * Checks url for public access
	 * @param string $publicUrl
	 * @return boolean
	 */
	private function isPublicUrl($publicUrl)
	{
		$publicUrlList = $this->getPublicUrlList();
		$publicUrl = rtrim($publicUrl, '/');

		return in_array($publicUrl, $publicUrlList);
	}

	/**
	 * Generate response object
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		if ($request instanceof Request\HttpRequest) {
			return new Response\TwigResponse();
		}
		if ($request instanceof Request\CliRequest) {
			return new Response\CliResponse();
		}

		return new Response\EmptyResponse();
	}

	/**
	 * Validates redirect url, if url contain collen then will return cms path
	 * @param string $redirectTo
	 * @return string 
	 */
	private function validateRedirectUrl($redirectTo = null)
	{
		if ( ! empty($redirectTo)) {
			//validate
			$externalUrl = strpos($redirectTo, ':');

			if ($externalUrl === false) {
				$redirectTo = '/' . trim($redirectTo, '/');
				
				return $redirectTo;
			}
		}

		return $this->getBasePath();
	}

	/**
	 * Returns redirect url or cms path
	 * @return string
	 */
	protected function getSuccessRedirectUrl()
	{
		$redirectTo = $this->getRequest()->getQueryValue('redirect_to');

		// returns redirect url or cms path
		$uri = $this->validateRedirectUrl($redirectTo);

		return $uri;
	}

}