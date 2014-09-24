<?php

namespace Supra\Package\CmsAuthentication\Event\Listener;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\RequestResponseEvent;
use Supra\Core\Event\RequestResponseListenerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CmsAuthenticationRequestListener implements RequestResponseListenerInterface, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * @param RequestResponseEvent $event
	 */
	public function listen(RequestResponseEvent $event)
	{
		$request = $event->getRequest();

		$cmsPrefix = $this->container->getParameter('cms.prefix');

		if (strpos($request->getPathInfo(), $cmsPrefix) === 0) {
			//in any way we should try to extract data from session
			$session = $this->container->getSession();

			$tokenParameter = $this->container->getParameter('cms_authentication.session_storage_key');

			$securityContext = $this->container->getSecurityContext();

			if ($session->has($tokenParameter)) {
				$securityContext->setToken(
					$session->get($tokenParameter)
				);
			}

			//non-authorized users that are not on anonymous paths are getting redirected to login
			if ((!$securityContext->getToken() ||
				!$securityContext->getToken()->getUser()) &&
				!in_array(
					$request->getPathInfo(),
					$this->container->getParameter('cms_authentication.anonymous_paths')
				)
			) {
				$event->setResponse(new RedirectResponse(
					$this->container->getRouter()->generate('cms_authentication_login')
				));
			}

			//authorized users on login path are redirected to dashboard
			if ($securityContext->getToken() &&
				$securityContext->getToken()->getUser() &&
				strpos($request->getPathInfo(), $this->container->getParameter('cms_authentication.login_path')) === 0
			) {
				$event->setResponse(new RedirectResponse($cmsPrefix));
			}
		}
	}

}
