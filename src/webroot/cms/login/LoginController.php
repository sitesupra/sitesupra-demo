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
 */
class LoginController extends SimpleController
{
	/**
	 * Default action when no action is provided
	 * @var string
	 */
	protected static $defaultAction = 'index';
	
	public function indexAction()
	{	
		$session = ObjectRepository::getSessionNamespace($this);
		
		if(!empty($session->login)) {
			$this->getResponse()->assign('email', $session->login);
		}
		
		if(!empty($session->message)) {
			$this->getResponse()->assign('message', $session->message);
			unset($session->message);
		}
		
		//TODO: should make short somehow!
		$this->getResponse()->outputTemplate('webroot/cms/login/index.html.twig');
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