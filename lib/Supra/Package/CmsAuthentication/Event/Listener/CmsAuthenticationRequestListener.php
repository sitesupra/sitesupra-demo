<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Supra\Package\CmsAuthentication\Event\Listener;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\DataAgnosticEvent;
use Supra\Core\Event\RequestResponseEvent;
use Supra\Core\Event\RequestResponseListenerInterface;
use Supra\Package\CmsAuthentication\Controller\AuthController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

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

			$tokenParameter = $this->container->getParameter('cms_authentication.session.storage_key');

			$securityContext = $this->container->getSecurityContext();

			if ($session->has($tokenParameter)) {
				$securityContext->setToken(
					$session->get($tokenParameter)
				);

				$this->container->getEventDispatcher()->dispatch(AuthController::TOKEN_CHANGE_EVENT, new DataAgnosticEvent());
			}

			//non-authorized users that are not on anonymous paths are getting redirected to login
			if ((!$securityContext->getToken() ||
				!$securityContext->getToken()->getUser()) &&
				!in_array(
					$request->getPathInfo(),
					$this->container->getParameter('cms_authentication.paths.anonymous')
				)
			) {
				if ($request->isXmlHttpRequest()) {
					$event->setResponse(new Response(AuthController::EMPTY_BODY, AuthController::FAILURE_STATUS));
				} else {
					$event->setResponse(new RedirectResponse(
						$this->container->getRouter()->generate('cms_authentication_login')
					));
				}

				$event->stopPropagation();
			}

			//authorized users on login path are redirected to dashboard
			if ($securityContext->getToken() &&
				$securityContext->getToken()->getUser() &&
				strpos($request->getPathInfo(), $this->container->getParameter('cms_authentication.paths.login')) === 0
			) {
				$event->setResponse(new RedirectResponse($cmsPrefix));
				$event->stopPropagation();
			}
		}
	}

}
