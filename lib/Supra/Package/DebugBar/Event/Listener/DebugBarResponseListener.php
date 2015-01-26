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

namespace Supra\Package\DebugBar\Event\Listener;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\RequestResponseEvent;
use Supra\Core\Event\RequestResponseListenerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DebugBarResponseListener implements ContainerAware, RequestResponseListenerInterface
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

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
		$response = $event->getResponse();

		if ($response instanceof BinaryFileResponse) {
			return;
		}

		if ($request->isXmlHttpRequest()) {
			//handle with ajax
			$debugBar = $this->container['debug_bar.debug_bar'];

			$response->headers->add($debugBar->getDataAsHeaders());
		} else {
			//replace http response
			if ($response->headers->get('content-type') &&
				$response->headers->get('content-type') != 'text/html') {
				return;
			}

			$debugBar = $this->container['debug_bar.debug_bar'];

			$renderer = $debugBar->getJavascriptRenderer();
			/* @var $renderer \DebugBar\JavascriptRenderer */
			$renderer->setBaseUrl('/public/debugbar');

			$body = $event->getResponse()->getContent();

			$body = str_ireplace(
				array('</head>', '</body>'),
				array(
					$renderer->renderHead() . PHP_EOL . '</head>',
					$renderer->render() . PHP_EOL . '</body>',
				),
				$body
			);

			$response->setContent($body);
		}
	}

}