<?php

namespace Project\Authentication;

use Supra\Controller;
use Supra\Controller\Exception;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Http\Cookie;

/**
 * Authentication PreFilter
 *
 * @author Dmitry Polovka <dmitry.polovka@videinfra.com>
 */
class AuthenticationPreFilterController extends Controller\ControllerAbstraction implements Controller\PreFilterInterface
{

	private $loginPath = '/cms/login';
	private $cmsPath = '/cms';
	private $loginField = 'supra_login';
	private $passwordField = 'supra_password';
	private $sessionName = 'SID';

	const REDIRECT_TO = 'supra_redirect_to';

	protected $userProvider;

	public function __construct()
	{
		$this->userProvider = ObjectRepository::getUserProvider($this);
	}

	public function getLoginField()
	{
		return $this->loginField;
	}

	public function setLoginField($loginField)
	{
		$this->loginField = $loginField;
	}

	public function getPasswordField()
	{
		return $this->passwordField;
	}

	public function setPasswordField($passwordField)
	{
		$this->passwordField = $passwordField;
	}

	public function getLoginPath()
	{
		return $this->loginPath;
	}

	public function setLoginPath($loginPath)
	{
		$this->loginPath = $loginPath;
	}

	public function getCmsPath()
	{
		return $this->cmsPath;
	}

	public function setCmsPath($cmsPath)
	{
		$this->cmsPath = $cmsPath;
	}

	public function execute()
	{
		session_name($this->sessionName);
		session_start();
		
		$post = $this->request->isPost();
		
		// if post request then check for login and password fields presence
		if ($post) {

			// login and password fields name
			$loginField = $this->getLoginField();
			$passwordField = $this->getPasswordField();

			// login and password
			$login = $this->request->getPostValue($loginField);
			$password = $this->request->getPostValue($passwordField);

			if ( ! empty($login) && ! empty($password)) {
				
				$userProvider = ObjectRepository::getUserProvider($this);	
				$user = $userProvider->authenticate($login, $password);
				
				if ( ! empty($user)) {
					$uri = $this->getCmsPath();
					
					if ( ! empty($_COOKIE[self::REDIRECT_TO])) {
						$uri = $_COOKIE[self::REDIRECT_TO];
					}
					
					$_SESSION['user'] = $user;
					
					$this->response->redirect($uri);
					
					$cookie = new Cookie(self::REDIRECT_TO, '');
					$cookie->setExpire('-1 min');
					
				} else {
					$loginPath = $this->getLoginPath();
					$this->response->redirect($loginPath);

					throw new Exception\StopRequestException();
				}
			}
		}

		$session = $_SESSION['user'];

		if (empty($session)) {

			$loginPath = $this->getLoginPath();
			$uri = $this->request->getRequestUri();

			if ($uri != $loginPath) {
				$cookie = new Cookie(self::REDIRECT_TO, $uri);
				$cookie->setExpire('+1 min');
				$cookie->setPath($this->getCmsPath());

				// FIXME: Ugly
				$domain = $_SERVER['HTTP_HOST'];
				$cookie->setDomain($domain);

				$this->response->setCookie($cookie);

				$this->response->redirect($loginPath);

				throw new Exception\StopRequestException();
			}
		}
	}

}