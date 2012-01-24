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
use Supra\Session\SessionNamespace;

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
	 * Check session action path
	 * @var string
	 */
	protected $checkSessionPath = 'check-session';
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
		$trimFunction = function ($value) {
			return trim($value, '/');
		};
		$list = array_map($trimFunction, $this->publicUrlList);
		
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
		$session = $sessionManager->getDefaultSessionNamespace();
		/* @var $session SessionNamespace */
		
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
		$userProvider = ObjectRepository::getUserProvider($this);
		
		$loginPath = $this->getLoginPath();
		$uri = $this->getRequest()->getActionString();

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
					
					$user = $userProvider->authenticate($login, $password);
					
					$userProvider->signIn($user);

					if ($xmlHttpRequest) {
						$this->response->setCode(200);
					} else {
						$successUri = $this->getSuccessRedirectUrl();
						$this->response->redirect($successUri);
					}

					$auditLog = ObjectRepository::getAuditLogger($this);
					$auditLog->info(null, 'login', "User '{$user->getEmail()}' logged in", $user);
					
					throw new StopRequestException("Login success");
					
				} catch (Exception\AuthenticationFailure $exc) {
					//TODO: pass the failure message somehow
					
					// Login not successfull
					$message = 'Incorrect login name or password';
					
					//TODO: i18n
					if ($exc instanceof Exception\ExistingSessionLimitation) {
						$message = $exc->getMessage();
					}

					if ($xmlHttpRequest) {
						$this->response->setCode(401);
						$this->response->header('X-Authentication-Pre-Filter-Message', $message);
						
					} else {

						$session->login = $login;
						$session->message = $message;

						$request = $this->request;

						/* @var $request Request\HttpRequest */

						// if authentication failed, we redirect user to login page
						$path = new Path($loginPath);
						$request->setPath($path);

						// Continue the request with login request path
						return;
					}

					throw new StopRequestException("Login failure");
				}
			}
		}

		// by default, we update session access time on each request
		// the only case when we need to skip this step is
		// when request is "session check" action
		$updateSession = true;
		$userActivity = $request->getParameter('activity', null);
		$checkSessionPath = trim($this->getBasePath() . '/' . $this->checkSessionPath, '/');
		if ($uri == $checkSessionPath && $userActivity === 'false') {
			$updateSession = false;
		}
		$sessionUser = $userProvider->getSignedInUser($updateSession);

		// if session is empty we redirect user to login page
		if (empty($sessionUser)) {

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
		$publicUrl = trim($publicUrl, '/');

		return in_array($publicUrl, $publicUrlList);
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