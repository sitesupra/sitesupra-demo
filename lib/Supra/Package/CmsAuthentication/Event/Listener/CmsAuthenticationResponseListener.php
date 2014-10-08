<?php

namespace Supra\Package\CmsAuthentication\Event\Listener;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\RequestResponseEvent;
use Supra\Core\Event\RequestResponseListenerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CmsAuthenticationResponseListener implements RequestResponseListenerInterface, ContainerAware
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
		$context = $this->container->getSecurityContext();

		if ($context->getToken() &&
			$context->getToken()->getUser()
		) {
			$this->container->getSession()->set(
				$this->container->getParameter('cms_authentication.session.storage_key'),
				$context->getToken()
			);
		}
	}

}
