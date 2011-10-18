<?php

namespace Project\SampleAuthentication;

use Supra\Controller\SimpleController;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Log\Log;
use Supra\Request;
use Supra\Response;

/**
 * Login controller
 * @method Response\TwigResponse getResponse()
 * @method Request\HttpRequest getRequest()
 */
class AuthenticateController extends SimpleController
{
	/**
	 * Index action
	 */
	public function indexAction()
	{
		$basePath = $this->getRequest()->getPath()
				->getBasePath();
		$this->getResponse()->assign('basePath', $basePath);
		
		$this->getResponse()->outputTemplate('template/index.html.twig');
	}

	public function loginAction()
	{
		$session = ObjectRepository::getSessionManager($this)
				->getAuthenticationSpace();

		if ( ! empty($session->login)) {
			$this->getResponse()->assign('email', $session->login);
			unset($session->login);
		}

		if ( ! empty($session->message)) {
			$this->getResponse()->assign('message', $session->message);
			unset($session->message);
		}

		$this->getResponse()->outputTemplate('template/login.html.twig');
	}

	public function logoutAction()
	{
		$session = ObjectRepository::getSessionManager($this)
				->getAuthenticationSpace();
		
		$user = $session->getUser();

		if ( ! empty($user)) {
			$session->removeUser();
		}

		$basePath = $this->getRequest()->getPath()
				->getBasePath();
		$this->getResponse()->assign('basePath', $basePath);
		
		$this->getResponse()->outputTemplate('template/logout.html.twig');
	}

	/**
	 * Generate response object
	 * @param Request\RequestInterface $request
	 * @return Response\TwigResponse
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		return new Response\TwigResponse($this);
	}

}