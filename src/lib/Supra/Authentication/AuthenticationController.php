<?php

namespace Supra\Authentication;

use Supra\Controller\ControllerAbstraction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller;
use Supra\Controller\Exception\StopRequestException;
use Supra\Request;
use Supra\Response;
use Supra\User;

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
	 * @var AuthenticationSessionNamespace
	 */
	protected $session;
	
	/**
	 * @var \Supra\Session\SessionManager
	 */
	protected $sessionManager;

	/**
	 * User Provider object instance
	 * @var User\UserProvider
	 */
	protected $userProvider;

	/**
	 * Public url list in restricted area
	 * @var array
	 */
	public $publicUrlList = array();

	/**
	 * Binds user provider and session
	 */
	public function __construct()
	{
		$this->session = ObjectRepository::getSessionNamespace($this);
		$this->sessionManager = ObjectRepository::getSessionManager($this);
		$this->userProvider = ObjectRepository::getUserProvider($this);
		
		if ( ! $this->session instanceof AuthenticationSessionNamespace) {
			throw new Exception\RuntimeException("Authentication session namespace is required for authentication prefilter");
		}
	}

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

			if ( ! empty($login) && ! $password->isEmpty()) {

				// Authenticating user
				$user = null;
				try {
					$user = $this->userProvider->authenticate($login, $password);
				} catch (Exception\AuthenticationFailure $exc) {
					//TODO: pass the failure message somehow
				}

				if ( ! empty($user)) {

					$uri = $this->getSuccessRedirectUrl();

					$this->session->setUser($user);

					if ( ! empty($this->session->login)) {
						unset($this->session->login);
					}

					if ($xmlHttpRequest) {
						$this->response->setCode(200);
					} else {
						$this->response->redirect($uri);
					}

					throw new StopRequestException("Login success");
				} else {
					// if authentication failed, we redirect user to login page
					$loginPath = $this->getLoginPath();
					$message = 'Incorrect login name or password';

					$redirectTo = $this->getRequest()->getQueryValue('redirect_to');
					
					if ( ! empty($redirectTo)) {
						$loginPath = $loginPath . '?redirect_to=' . urlencode($redirectTo);
					}

					if ($xmlHttpRequest) {
						$this->response->setCode(401);
						$this->response->header('X-Authentication-Pre-Filter-Message', $message);
					} else {

						$this->response->redirect('/' . $loginPath);
						$this->session->login = $login;
						$this->session->message = $message;
					}

					throw new StopRequestException("Login failure");
				}
			}
		}

		$sessionUser = null;

		// check for session presence
		$user = $this->session->getUser();
		if ( ! empty($user)) {
			$sessionUser = $user;
		}

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