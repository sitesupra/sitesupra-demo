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

namespace Supra\Package\Framework\Command;

use Supra\Core\Console\AbstractCommand;
use Supra\Core\Event\ConsoleEvent;
use Supra\Core\Package\PackageLocator;
use Supra\Package\Framework\Event\FrameworkConsoleEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AssetsPublishCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('assets:publish')
			->setDescription('Symlinks package assets into web root');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$application = $this->container->getApplication();
		$webRoot = $application->getWebRoot();

		foreach ($application->getPackages() as $package) {
			$target = $application->locatePublicFolder($package);
			$link = $this->container->getParameter('directories.public') . DIRECTORY_SEPARATOR . $package->getName();

			if (!is_dir($this->container->getParameter('directories.public'))) {
				mkdir($this->container->getParameter('directories.public'), 0777, true);
			}

			if (is_dir($target)) {
				$output->writeln(sprintf(
					'Publishing assets for package <info>%s</info>, <info>%s</info> -> <info>%s</info>',
					$package->getName(),
					$target,
					$link
				));

				if (is_link($link)) {
					unlink($link);
				}

				symlink($target, $link);
			} else {
				$output->writeln(sprintf(
					'Skipping assets for package <info>%s</info>, no <info>Resources/public</info> folder found', $package->getName()
				));
			}
		}

		$event = new ConsoleEvent($this, $input, $output);
		$event->setData(array(
			'webRoot' => $webRoot,
			'webRootPublic' => $webRoot . '/public/'
		));

		$this->container->getEventDispatcher()->dispatch(FrameworkConsoleEvent::ASSETS_PUBLISH, $event);
	}

}
