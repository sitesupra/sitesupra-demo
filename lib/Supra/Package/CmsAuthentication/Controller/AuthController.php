<?php

namespace Supra\Package\CmsAuthentication\Controller;

use Supra\Core\Controller\Controller;
use Supra\Core\Event\DataAgnosticEvent;
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
	const PRE_AUTHENTICATE_EVENT = 'cms_authentication.pre_authenticate';
	const POST_AUTHENTICATE_EVENT = 'cms_authentication.post_authenticate';
	const AUTHENTICATION_EXCEPTION_EVENT = 'cms_authentication.exception';
	const AUTHENTICATION_RESULT_EVENT = 'cms_authentication.result';

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

		$authenticationManager = $this->container['cms_authentication.users.authentication_manager'];
		/* @var $authenticationManager AuthenticationProviderManager */

		$event = new DataAgnosticEvent();
		$event->setData(array('username' => $username, 'password' => $password, 'result' => null));

		try {
			$this->container->getEventDispatcher()->dispatch(self::PRE_AUTHENTICATE_EVENT, $event);

			$result = $authenticationManager->authenticate(
				new UsernamePasswordToken($event->getData()['username'], $event->getData()['password'], $this->container->getParameter('cms_authentication.provider_key'))
			);

			$event->setData(array_merge($event->getData(), array('result' => $result)));

			$this->container->getEventDispatcher()->dispatch(self::POST_AUTHENTICATE_EVENT, $event);

			$result = $event->getData()['result'];
		} catch (BadCredentialsException $e) {
			$event->setData(array_merge($event->getData(), array('exception' => $e)));

			$this->container->getEventDispatcher()->dispatch(self::AUTHENTICATION_EXCEPTION_EVENT, $event);

			if ($event->getData()['result'] instanceof Response) {
				return $event->getData()['result'];
			}

			//if password is not valid Symfony throws plain BadCredentialException, so we can put "Invalid password" here
			$message = 'Incorrect login name or password';

			$previous = $e->getPrevious();

			if ($previous instanceof UsernameNotFoundException) {
				//"such username does not exist"
				$message = 'Incorrect login name or password';
			}

			return new Response(
				self::EMPTY_BODY,
				self::FAILURE_STATUS,
				array(self::HEADER_401_MESSAGE => $message)
			);
		}

		$event->setData($event->getData(), array('result' => $result));

		$this->container->getEventDispatcher()->dispatch(self::AUTHENTICATION_RESULT_EVENT, $event);

		$result = $event->getData()['result'];

		if ($event->getData()['result'] instanceof Response) {
			return $event->getData()['result'];
		}

		if ($result instanceof TokenInterface) {
			$this->container->getSecurityContext()->setToken($result);

			return new Response(
				self::EMPTY_BODY
			);
		}

		//catch-all
		return new Response(
			AuthController::EMPTY_BODY,
			AuthController::FAILURE_STATUS,
			array(AuthController::HEADER_401_MESSAGE => 'Unknown authentication error')
		);
	}
}