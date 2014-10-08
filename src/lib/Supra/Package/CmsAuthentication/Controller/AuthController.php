<?php

namespace Supra\Package\CmsAuthentication\Controller;

use Supra\Core\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class AuthController extends Controller
{
	const HEADER_401_MESSAGE = 'X-Authentication-Pre-Filter-Message';
	const HEADER_401_REDIRECT = 'X-Authentication-Pre-Filter-Redirect';
	const FAILURE_STATUS = 401;
	const EMPTY_BODY = '1';

	/**
	 * @var string
	 */
	protected $application = 'cms_authentication';

	public function loginAction()
	{
		return $this->renderResponse('auth/login.html.twig', array());
	}

	public function logoutAction()
	{
		$this->container->getSession()->invalidate();
		$this->container->getSecurityContext()->setToken(null);

		return new RedirectResponse($this->container->getParameter('cms.prefix'));
	}

	public function checkAction(Request $request)
	{
		$username = $request->request->get('supra_login', 'admin');
		$password = $request->request->get('supra_password', 'admin');

		//success results send cookie AND "1" as response body
		//password redirect results send location
		//failures send 401 status and const HEADER_401_MESSAGE = 'X-Authentication-Pre-Filter-Message',
		//HEADER_401_REDIRECT = 'X-Authentication-Pre-Filter-Redirect'; something
		//currently messages for both exceptions are the same;
		//if ($exc instanceof Exception\WrongPasswordException) {
		//$message = 'Incorrect login name or password';
		//}
		//
		//if ($exc instanceof Exception\UserNotFoundException) {
		//$message = 'Incorrect login name or password';
		//}
		//so we can merge them in one response

		$authenticationManager = $this->container['cms_authentication.users.authentication_manager'];
		/* @var $authenticationManager AuthenticationProviderManager */

		try {
			$result = $authenticationManager->authenticate(
				new UsernamePasswordToken($username, $password, $this->container->getParameter('cms_authentication.provider_key'))
			);
		} catch (BadCredentialsException $e) {
			//if password is not valid Symfony throws plain BadCredentialException, so we can put "Invalid password" here
			$message = 'Incorrect login name or password';

			$previous = $e->getPrevious();

			if ($previous instanceof UsernameNotFoundException) {
				//"such username does not exist"
				$message = 'Incorrect login name or password';
			}

			return new Response(
				$this::EMPTY_BODY,
				$this::FAILURE_STATUS,
				array($this::HEADER_401_MESSAGE => $message)
			);
		}

		if ($result instanceof TokenInterface) {
			$this->container->getSecurityContext()->setToken($result);

			return new Response(
				$this::EMPTY_BODY
			);
		}

		//catch-all
		return new Response(
			$this::EMPTY_BODY,
			$this::FAILURE_STATUS,
			array($this::HEADER_401_MESSAGE => 'Unknown authentication error')
		);
	}
}