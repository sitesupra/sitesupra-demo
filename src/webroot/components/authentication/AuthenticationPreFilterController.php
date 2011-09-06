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
	
	/**
	 * Session expiration time in seconds
	 */
	const SESSION_EXPIRATION_TIME = 900;

	protected $userProvider;

	/**
	 * TODO: when you will be able to pass to $routerConfiguration->controller object instead of namespace
	 * you will need to add setters and getters for public url list
	 * 
	 * Public url list in restricted area
	 * @var array
	 */
	public $publicUrlList = array(
		'/cms/internal-user-manager/restore',
		'/cms/internal-user-manager/restore/changepassword',
	);

	public function getPublicUrlList()
	{
		return $this->publicUrlList;
	}

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
		
		$isPublicUrl = $this->isPublicUrl($this->request->getRequestUri());

		if ($isPublicUrl) {
			return;
		}

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
					
					$redirect_to = $this->request->getCookie(self::REDIRECT_TO);
					if ( ! empty($redirect_to)) {
						$uri = $redirect_to;
					}

					$_SESSION['user'] = $user;
					$_SESSION['expiration_time'] = time()+self::SESSION_EXPIRATION_TIME;
					
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

		$session = null;
		
		if( ! empty ($_SESSION['user'])) {
			$time = time();
			if($_SESSION['expiration_time'] > $time) {
				$session = $_SESSION['user'];
			} else {
				unset ($_SESSION['user']);
			}
			
		}

		if (empty($session)) {

			$loginPath = $this->getLoginPath();
			$uri = $this->request->getRequestUri();

			if ($uri != $loginPath) {
				$cookie = new Cookie(self::REDIRECT_TO, $uri);
				$cookie->setExpire('+1 min');
				$cookie->setPath($this->getCmsPath());

				// FIXME: Ugly
				$domain = $this->request->getServerValue('HTTP_HOST');
				$cookie->setDomain($domain);

				$this->response->setCookie($cookie);

				$this->response->redirect($loginPath);

				throw new Exception\StopRequestException();
			}
		}
	}

	private function isPublicUrl($publicUrl)
	{
		$publicUrlList = $this->getPublicUrlList();
		$publicUrl = rtrim($publicUrl, '/');

		return in_array($publicUrl, $publicUrlList);
	}

}