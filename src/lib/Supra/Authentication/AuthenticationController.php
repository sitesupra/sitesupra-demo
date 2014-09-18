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
use Supra\Password\PasswordPolicyInterface;
use Supra\Password\Exception\PasswordExpiredException;

/**
 * Authentication controller
 */
abstract class AuthenticationController extends ControllerAbstraction implements Controller\PreFilterInterface
{
	
	const HEADER_401_MESSAGE = 'X-Authentication-Pre-Filter-Message',
			HEADER_401_REDIRECT = 'X-Authentication-Pre-Filter-Redirect';
	
	/**
	 * Login page path
	 * @var string
	 */
	protected $loginPath = 'login';

	/**
	 * @var string
	 */
	protected $passwordPath = 'login/mypassword';

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
	 * Defines, will be user redirected on login success 
	 * @var boolean
	 */
	protected $skipRedirect = false;
	
	/**
     *
	 */
	protected $signedInUser;

	/**
	 * Public URL list
	 * @return array
	 */
	public function getPublicUrlList()
	{
		$list = $this->prepareUrlList($this->publicUrlList);

		return $list;
	}

	/**
	 * Cleanup URLs set for filtering
	 * @param array $list
	 * @return array 
	 */
	public function prepareUrlList($list)
	{

		$trimFunction = function ($value) {
					return trim($value, '/');
				};
		$list = array_map($trimFunction, $list);

		return $list;
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
	 * @return Path 
	 */
	public function getLoginPath()
	{
		return new Path($this->loginPath, '/');
	}
	
	/**
	 * 
	 * @return \Supra\Uri\Path
	 */
	public function getPasswordChangePath()
	{
		return new Path($this->passwordPath, '/');
	}
	
	/**
	 * Sets password change page path
	 * @param string $path 
	 */
	public function setPasswordChangePath($path)
	{
		$this->passwordPath = $path;
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
		$basePath = '/' . trim($this->basePath, '/');
		$basePath = rtrim($basePath, '/') . '/';

		return $basePath;
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
		$userProvider = ObjectRepository::getUserProvider($this);
		// TODO: maybe should fetch session manager by user provider
		//$sessionManager = ObjectRepository::getSessionManager($userProvider);
		$sessionManager = ObjectRepository::getSessionManager($this);

		// TODO: cerate special namespace for this
		$session = $sessionManager->getDefaultSessionNamespace();
		/* @var $session SessionNamespace */

		// Unset the session data
		unset($session->login, $session->message);

		$request = $this->getRequest();

		$xmlHttpRequest = ($request->getServerValue('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest' ? true : false);
		$path = $request->getPath();
		
		$isPost = $request->isPost();
		$isPublicUrl = $this->isPublicUrl($path);

		$loginPath = $this->getLoginPath();
		
		if ($isPost) {

			// login and password fields name
			$loginField = $this->getLoginField();
			$login = $this->getRequest()->getPostValue($loginField);
			
			$user = null;
			
			if ( ! empty($login)) {
				
				$passwordField = $this->getPasswordField();
				$plainPassword = $this->getRequest()->getPostValue($passwordField);

				$password = new AuthenticationPassword($plainPassword);
				
				
				$eventArgs = new Event\EventArgs($this);
				$eventArgs->request = $request;

				try {

					$eventManager = ObjectRepository::getEventManager($this);
					$eventManager->fire(Event\EventArgs::preAuthenticate, $eventArgs);

					$user = $userProvider->authenticate($login, $password);

					$userProvider->signIn($user);

					$auditLog = ObjectRepository::getAuditLogger($this);
					$auditLog->info($this, "User '{$user->getEmail()}' logged in", $user);

					// @TODO: move below the password validation?
					$eventManager->fire(Event\EventArgs::onAuthenticationSuccess, $eventArgs);
					
					$passwordPolicy = $userProvider->getPasswordPolicy();
					if ($passwordPolicy instanceof PasswordPolicyInterface) {
						$passwordPolicy->validateUserPasswordExpiration($user);
					}

					// 
					if ($xmlHttpRequest) {
						$this->response->setCode(200);
						$this->response->output('1');
					} else {
						if ( ! $this->skipRedirect) {
							$successUri = $this->getSuccessRedirectUrl($user);
							$this->response->redirect($successUri);
						} else {
							return;
						}
					}
					
					$userProvider->getSessionManager()->close();
					$sessionManager->close();

					throw new StopRequestException("Login success");

				} catch (Exception\AuthenticationFailure $exc) {

					$eventManager = ObjectRepository::getEventManager($this);
					$eventManager->fire(Event\EventArgs::onAuthenticationFailure, $eventArgs);
					$message = null;

					//TODO: pass the failure message somehow
					// Login not successfull
					if ($exc instanceof Exception\WrongPasswordException) {
						$message = 'Incorrect login name or password';
					}

					if ($exc instanceof Exception\UserNotFoundException) {
						$message = 'Incorrect login name or password';
					}

					if ($exc instanceof Exception\AuthenticationBanException) {
						$message = 'Too many authentication failures';
					}
					
					if ($exc instanceof PasswordExpiredException) {
						
						if ($path->equals($loginPath)) {
							$user->forcePasswordChange();
							$userProvider->updateUser($user);
						}
												
						if ( ! $xmlHttpRequest) {
							$message = 'Password is expired';
							$request->setPath($this->getPasswordChangePath());
							
							return;
						} else {
							$message = 'Your password has expired and for the security reasons it must be changed';
							
							$redirectPath = $this->getPasswordChangePath()
									->getPath(Path::FORMAT_BOTH_DELIMITERS);
							
							$this->response->header(self::HEADER_401_REDIRECT, $redirectPath);
						}
					}

					//TODO: i18n
					if (is_null($message)) {
						if ($exc instanceof Exception\ExistingSessionLimitation) {
							$message = $exc->getMessage();
						} else if ($exc instanceof Exception\AuthenticationFailure) {

							$message = $exc->getMessage();
						}
					}

					if ($xmlHttpRequest) {
						$this->response->setCode(401);
						$this->response->header(self::HEADER_401_MESSAGE, $message);
					} else {

						$session->login = $login;
						$session->message = $message;

						// if authentication failed, we redirect user to login page
						if ( ! $isPublicUrl) {
							$request->setPath($loginPath);
						}

						// Continue the request with login request path
						return;
					}

					throw new StopRequestException("Login failure");
				}
			}
		}

		// Allow accessign public URL
		if ($isPublicUrl) {
			return;
		}

		// by default, we update session access time on each request
		// the only case when we need to skip this step is
		// when request is "session check" action
		$updateSession = true;
		$userActivity = $request->getParameter('activity', null);
		$checkSessionPath = trim($this->getBasePath() . '/' . $this->checkSessionPath, '/');
		if ($path == $checkSessionPath && $userActivity === 'false') {
			$updateSession = false;
		}
		$sessionUser = $userProvider->getSignedInUser($updateSession);

		$passwordChangePath = $this->getPasswordChangePath();
		
		// if session is empty we redirect user to login page
		if (empty($sessionUser)) {

			if ( ! $path->equals($loginPath)) {

				if ($xmlHttpRequest) {
					$this->response->setCode(401);
					
					if ($path->startsWith($passwordChangePath)) {
						$this->response->header(self::HEADER_401_REDIRECT, $loginPath->getPath(Path::FORMAT_BOTH_DELIMITERS) . '?redirect_to=' . urlencode($fullUri));
					}
				} else {
					$fullUri = $this->getRequest()->getRequestUri();
					$this->response->redirect($loginPath->getPath(Path::FORMAT_BOTH_DELIMITERS) . '?redirect_to=' . urlencode($fullUri));
				}

				throw new StopRequestException("User not authenticated");
			}
		} else {

			
			
			if ($sessionUser->isForcedToChangePassword()) {
				
				if ( ! $path->startsWith($passwordChangePath)) {
					
					$redirectPath = $this->getPasswordChangePath()
									->getPath(Path::FORMAT_BOTH_DELIMITERS);
					
					if ($xmlHttpRequest) {
						$this->response->setCode(401);
	
						$this->response->header(self::HEADER_401_REDIRECT, $redirectPath);
						$this->response->header(self::HEADER_401_MESSAGE, 'Your password is expired');
					} else {
						$this->response->redirect($redirectPath . '?redirect_to=' . urlencode($fullUri));
						
					}	
					throw new StopRequestException("Password expired");
				}
				
				return;
			}
			
			// Redirect from login form if the session is active
			if ($path->equals($loginPath) || $path->startsWith($passwordChangePath)) {
				$redirect = $this->getSuccessRedirectUrl($user);
				$this->response->redirect($redirect);

				throw new StopRequestException("Session is already active");
			}
		}

		$userProvider->getSessionManager()->close();
		$sessionManager->close();
	}

	/**
	 * Checks url for public access
	 * @param Path $path
	 * @return boolean
	 */
	protected function isPublicUrl(Path $path)
	{
		$publicUrlList = $this->getPublicUrlList();

		foreach ($publicUrlList as $publicUrl) {
			$publicUrlPath = new Path($publicUrl);

			if ($path->equals($publicUrlPath)) {
				return true;
			}
		}

		return false;
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
				$redirectTo = rtrim($redirectTo, '/') . '/';

				return $redirectTo;
			}
		}

		return $this->getBasePath();
	}

	/**
	 * Returns redirect url or cms path
	 * @return string
	 */
	protected function getSuccessRedirectUrl($user)
	{
		$redirectTo = $this->getRequest()->getQueryValue('redirect_to');

		// returns redirect url or cms path
		$uri = $this->validateRedirectUrl($redirectTo);

		return $uri;
	}

	/**
	 *
	 * @param boolean $skip 
	 */
	public function setSkipRedirect($skip)
	{
		$this->skipRedirect = $skip;
	}

}