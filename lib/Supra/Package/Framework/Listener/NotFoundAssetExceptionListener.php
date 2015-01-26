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

namespace Supra\Package\Framework\Listener;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Filter\LessphpFilter;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\RequestResponseEvent;
use Supra\Core\Event\RequestResponseListenerInterface;
use Symfony\Component\HttpFoundation\Response;

class NotFoundAssetExceptionListener implements RequestResponseListenerInterface, ContainerAware
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
		$path = $event->getRequest()->getPathInfo();

		$parts = pathinfo($path);

		$parts = array_merge(array(
			'dirname' => '',
			'basename' => '',
			'extension' => '',
			'filename'
		), $parts);

		switch (strtolower($parts['extension'])) {
			case 'css':
				//possible it's not yet compiled less file?
				$lessFile = $this->container->getApplication()->getWebRoot().$path.'.less';

				if (is_file($lessFile)) {
					$asset = new FileAsset($lessFile);
					$asset->ensureFilter(new LessphpFilter());

					$content = $this->container->getCache()->fetch('assets_404', $path, function () use ($asset) {
						return $asset->dump();
					}, $asset->getLastModified(), 0, true);

					$event->setResponse(new Response($content, 200, array('Content-Type' => 'text/css')));

					$event->stopPropagation();

					return;
				}
				break;
		}
	}

}
