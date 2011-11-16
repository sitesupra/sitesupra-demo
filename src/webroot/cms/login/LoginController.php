<?php

namespace Supra\Cms\Login;

use Supra\Controller\SimpleController;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Log\Log;
use Supra\Request;
use Supra\Response;

/**
 * Login controller
 * @method Response\TwigResponse getResponse()
 */
class LoginController extends SimpleController
{

	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected $defaultAction = 'index';

	public function indexAction()
	{
		$session = ObjectRepository::getSessionManager($this)
				->getAuthenticationSpace();
		$response = $this->getResponse();

		if ( ! empty($session->login)) {
			$response->assign('email', $session->login);
			unset($session->login);
		}

		if ( ! empty($session->message)) {
			$response->assign('message', $session->message);
			unset($session->message);
		}

		//TODO: should make short somehow!
		$response->outputTemplate('index.html.twig');
		
		// Could fix the problem with login page cache
		$response->forbidCache();
	}

	/**
	 * Generate response object
	 * @param Request\RequestInterface $request
	 * @return Response\ResponseInterface
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		if ( ! $request instanceof Request\HttpRequest) {
			throw new \Supra\Request\Exception\InvalidRequest("Only HTTP requests are allowed");
		}
		
		return new Response\TwigResponse($this);
	}

}