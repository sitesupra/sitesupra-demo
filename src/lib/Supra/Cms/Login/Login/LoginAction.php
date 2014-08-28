<?php

namespace Supra\Cms\Login\Login;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Request;
use Supra\Response;
use Supra\Cms\CmsAction;

/**
 * Login controller
 * @method Response\TwigResponse getResponse()
 */
class LoginAction extends CmsAction
{
	
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
		
		// TODO: Hardcoded
		$manager = new \Supra\Cms\ApplicationConfiguration();
		$manager->id = 'login';
		$manager->title = 'Login';
		$manager->url = 'login';
		$manager->configure();
		
		$ini = ObjectRepository::getIniConfigurationLoader($this);
		$passwordResetUri = $ini->getValue('cms', 'password_reset_uri', false);
		
		if ($passwordResetUri) {
			$response->assign('passwordResetUri', $passwordResetUri);
		}
		
		$response->assign('manager', $manager);

		$response->outputTemplate('login/index.html.twig');
		
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
		return $this->createTwigResponse();
	}

}
