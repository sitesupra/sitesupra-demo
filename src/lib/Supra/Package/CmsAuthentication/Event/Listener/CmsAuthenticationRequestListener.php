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
			$securityContext = $this->container->getSecurityContext();

			if ((!$securityContext->getToken() ||
				!$securityContext->getToken()->isAuthenticated()) &&
				!in_array(
					$request->getPathInfo(),
					$this->container->getParameter('cms_authentication.anonymous_paths')
				)
			) {
				$event->setResponse(new RedirectResponse(
					$this->container->getRouter()->generate('cms_authentication_login')
				));
			}
		}
	}

}
