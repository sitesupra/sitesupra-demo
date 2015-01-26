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

namespace Supra\Package\Cms\Pages\Listener;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\RequestResponseEvent;
use Supra\Core\Event\RequestResponseListenerInterface;
use Supra\Package\Cms\Pages\Request\PageRequestView;


class NotFoundExceptionListener implements RequestResponseListenerInterface, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @param \Supra\Core\DependencyInjection\ContainerInterface $container
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
		if ($event->hasResponse()) {
			// do nothing if response exists already
			return;
		}

		$request = $event->getRequest();

		$pageController = $this->container['cms.pages.controller'];
		/* @var $pageController \Supra\Package\Cms\Controller\PageController */

		$request->attributes->set('path', '404');
		
		$pageRequest = new PageRequestView($request);
		$pageRequest->setContainer($this->container);

		try {
			$event->setResponse(
					$pageController->execute($pageRequest)
			);
		} catch (ResourceNotFoundException $e) {} // ignore silently
	}
}
