<?php

namespace Project\Authenticate;

use Supra\Controller\SimpleController;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Log\Log;
use Supra\Request;
use Supra\Response;

/**
 * Login controller
 */
class AuthenticateController extends SimpleController
{

	/**
	 * Login page path
	 * @var string
	 */
	//TODO: Move configuration to Configuration object
	public $loginPage = '/authenticate/login';
	
	public function getLoginPage()
	{
		return $this->loginPage;
	}
	
	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected static $defaultAction = 'index';

	public function indexAction()
	{
		echo 'i am index action';
	}

	public function loginAction()
	{
		$session = ObjectRepository::getSessionNamespace($this);

		if ( ! empty($session->login)) {
			$this->getResponse()->assign('email', $session->login);
			unset($session->login);
		}

		if ( ! empty($session->message)) {
			$this->getResponse()->assign('message', $session->message);
			unset($session->message);
		}

		//TODO: should make short somehow!
		$this->getResponse()->outputTemplate('webroot/components/Authenticate/index.html.twig');
	}

	public function logoutAction()
	{
		$session = ObjectRepository::getSessionNamespace($this);

		$loginPage = $this->getLoginPage();

		$user = $session->getUser();

		if ( ! empty($user)) {
			$session->removeUser();
		}

		$this->response->redirect($loginPage);
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