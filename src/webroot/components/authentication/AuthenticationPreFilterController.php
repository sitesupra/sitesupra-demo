<?php

namespace Project\Authentication;

use Supra\Controller;
use Supra\Controller\Exception;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Http\Cookie;
use Supra\Request;
use Supra\Response;
use Supra\User;

/**
 * Authentication PreFilter
 *
 * @author Dmitry Polovka <dmitry.polovka@videinfra.com>
 */
class AuthenticationPreFilterController extends Controller\ControllerAbstraction implements Controller\PreFilterInterface
{

	/**
	 * Login page path
	 * @var string
	 */
	private $loginPath = '/cms/login';

	/**
	 * Cms page path
	 * @var string
	 */
	private $cmsPath = '/cms';

	/**
	 * Login field name on login page
	 * @var string
	 */
	private $loginField = 'supra_login';

	/**
	 * Password field name on login page
	 * @var string
	 */
	private $passwordField = 'supra_password';

	/**
	 * Session name
	 * @var string
	 */
	private $sessionName = 'SID';

	/**
	 * "Redirect to" cookie name
	 */
	const REDIRECT_TO = 'supra_redirect_to';

	/**
	 * Session expiration time in seconds
	 */
	const SESSION_EXPIRATION_TIME = 900;

	/**
	 * User Provider object instance
	 * @var \Supra\User\UserProvider
	 */
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

	public function __construct()
	{
		$this->userProvider = ObjectRepository::getUserProvider($this);
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
		return $this->loginPath;
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
	 * Returns cms page path
	 * @return string 
	 */
	public function getCmsPath()
	{
		return $this->cmsPath;
	}

	/**
	 * Sets cms page path
	 * @param string $cmsPath 
	 */
	public function setCmsPath($cmsPath)
	{
		$this->cmsPath = $cmsPath;
	}

	/**
	 * Main executable action
	 */
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

				// Authenticating user
				$user = null;
				try {
					$user = $this->userProvider->authenticate($login, $password);
				} catch (User\Exception\AuthenticationExeption $exc) {
					
				}


				if ( ! empty($user)) {
					$uri = $this->getCmsPath();

					$redirect_to = $this->request->getCookie(self::REDIRECT_TO);

					// if is set "redirect to" then rewriting redirect uri to "redirect to" value
					if ( ! empty($redirect_to)) {
						$uri = $redirect_to;
					}

					$_SESSION['user'] = $user;
					$_SESSION['expiration_time'] = time() + self::SESSION_EXPIRATION_TIME;

					$this->response->redirect($uri);

					// Reseting "redirect to" cookie
					$cookie = new Cookie(self::REDIRECT_TO, '');
					$cookie->setExpire('-1 min');
				} else {
					// if authentication failed, we redirect user to login page
					$loginPath = $this->getLoginPath();
					$this->response->redirect($loginPath);
					$_SESSION['login'] = $login;
					$_SESSION['message'] = 'Incorrect login name or password';
					throw new Exception\StopRequestException();
				}
			}
		}

		$session = null;

		// check for session presence and session expiration time
		if ( ! empty($_SESSION['user'])) {
			$time = time();
			if ($_SESSION['expiration_time'] > $time) {
				$session = $_SESSION['user'];
			} else {
				unset($_SESSION['user']);
			}
		}

		// if session is empty we redirect user to login page
		if (empty($session)) {

			$loginPath = $this->getLoginPath();
			$uri = $this->request->getRequestUri();

			if ($uri != $loginPath) {
				$cookie = new Cookie(self::REDIRECT_TO, $uri);
				$cookie->setExpire('+1 min');
				$cookie->setPath($this->getCmsPath());

				// FIXME: Ugly
				$domain = $this->request->getServerValue('HTTP_HOST');

				$this->response->setCookie($cookie);

				$this->response->redirect($loginPath);

				throw new Exception\StopRequestException();
			}
		} else {
			$cmsPath = $this->getCmsPath();
			$uri = $this->request->getRequestUri();

			if ($uri != $cmsPath) {
				$this->response->redirect($cmsPath);
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

}