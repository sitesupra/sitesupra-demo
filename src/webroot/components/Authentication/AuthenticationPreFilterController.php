<?php

namespace Project\Authentication;

use Supra\Controller;
use Supra\Controller\Exception;
use Supra\ObjectRepository\ObjectRepository;
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
	 * @var Session\SessionNamespace
	 */
	private $session;

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
		$this->session = ObjectRepository::getSessionNamespace($this);
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
		$isPublicUrl = $this->isPublicUrl($this->request->getRequestUri());

		$requestedWith = $this->request->getServerValue('HTTP_X_REQUESTED_WITH');

		$xmlHttpRequest = false;

		if ($requestedWith == 'XMLHttpRequest') {
			$xmlHttpRequest = true;
		}

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
					
					$redirect_to = $this->request->getQueryValue('redirect_to');
					
					// returns redirect url or cms path
					$uri = $this->validateRedirectUrl($redirect_to);
					
					$this->session->setUser($user);

					if ($xmlHttpRequest) {
						$this->response->setCode(200);
					} else {
						$this->response->redirect($uri);
					}
					
					throw new Exception\StopRequestException();
					
				} else {
					// if authentication failed, we redirect user to login page
					$loginPath = $this->getLoginPath();
					$message = 'Incorrect login name or password';

					if ($xmlHttpRequest) {
						$this->response->setCode(401);
						$this->response->header('X-Authentication-Pre-Filter-Message', $message);
					} else {
						$this->response->redirect($loginPath);
						$this->session->login = $login;
						$this->session->message = $message;
					}

					throw new Exception\StopRequestException();
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
			$uri = $this->request->getRequestUri();

			if ($uri != $loginPath) {
				$this->session->redirect_to = $uri;

				if ($xmlHttpRequest) {
					$this->response->setCode(401);
				} else {
					$this->response->redirect($loginPath .'?redirect_to=' . urlencode($uri));
				}

				throw new Exception\StopRequestException();
			}
		} else {
			$cmsPath = $this->getCmsPath();
			$loginPath = $this->getLoginPath();
			$uri = $this->request->getRequestUri();

			if ($uri == $loginPath) {
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

	/**
	 * Validates redirect url, if url contain collen then will return cms path
	 * @param string $redirect_to
	 * @return string 
	 */
	private function validateRedirectUrl($redirect_to = null)
	{
		if(!  empty ($redirect_to)) {
			$redirect_to = urldecode($redirect_to);
			
			//validate
			$externalUrl = strpos(':', $redirect_to);
			
			if ($externalUrl === false) {
				return $redirect_to;
			}
		}
		
		return $this->getCmsPath();
	}
}