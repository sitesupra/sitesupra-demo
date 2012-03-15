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
	 * Defines, will be user redirected on login success 
	 * @var boolean
	 */
	protected $skipRedirect = false;

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
	public function prepareUrlList($list) {
		
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
		$path = $this->getRequest()->getPath();

		$xmlHttpRequest = false;
		$requestedWith = $this->getRequest()->getServerValue('HTTP_X_REQUESTED_WITH');

		if ($requestedWith == 'XMLHttpRequest') {
			$xmlHttpRequest = true;
		}

		$post = $this->getRequest()->isPost();
		$isPublicUrl = $this->isPublicUrl($path);

		$loginPath = $this->getLoginPath();
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
				
				$eventArgs = new Event\EventArgs();
				$eventArgs->request = $request;

				try {

					// TODO: Maybe should be moved to some password policy guide with password expire features?
					if ($password->isEmpty()) {
						throw new Exception\WrongPasswordException("Empty passwords are not allowed");
					}
					
					$eventManager = ObjectRepository::getEventManager($this);
					$eventManager->fire(Event\EventArgs::preAuthenticate, $eventArgs);

					$user = $userProvider->authenticate($login, $password);

					$userProvider->signIn($user);

					$auditLog = ObjectRepository::getAuditLogger($this);
					$auditLog->info("User '{$user->getEmail()}' logged in", $user);
					
					$eventManager = ObjectRepository::getEventManager($this);
					$eventManager->fire(Event\EventArgs::onAuthenticationSuccess, $eventArgs);
					
					if ($xmlHttpRequest) {
						$this->response->setCode(200);
						$this->response->output('1');
					} else {
						if ( ! $this->skipRedirect) {
							$successUri = $this->getSuccessRedirectUrl();
							$this->response->redirect($successUri);
						} else {
							return;
						}
					}

					throw new StopRequestException("Login success");
				} catch (Exception\AuthenticationFailure $exc) {
					
					$eventManager = ObjectRepository::getEventManager($this);
					$eventManager->fire(Event\EventArgs::onAuthenticationFailure, $eventArgs);
					
					//TODO: pass the failure message somehow
					// Login not successfull
					$message = 'Incorrect login name or password';
					
					if ($exc instanceof Exception\AuthenticationBanException) {
						$message = 'Too many authentication failures';
					}

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

		// if session is empty we redirect user to login page
		if (empty($sessionUser)) {

			if ( ! $path->equals($loginPath)) {

				if ($xmlHttpRequest) {
					$this->response->setCode(401);
				} else {
					$fullUri = $this->getRequest()->getRequestUri();
					$this->response->redirect($loginPath->getPath(Path::FORMAT_BOTH_DELIMITERS) . '?redirect_to=' . urlencode($fullUri));
				}

				throw new StopRequestException("User not authenticated");
			}
		} else {

			// Redirect from login form if the session is active
			if ($path->equals($loginPath)) {
				$redirect = $this->getSuccessRedirectUrl();
				$this->response->redirect($redirect);

				throw new StopRequestException("Session is already active");
			}
		}
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
	
	/**
	 *
	 * @param boolean $skip 
	 */
	public function setSkipRedirect($skip) 
	{
		$this->skipRedirect = $skip;
	}
	
}