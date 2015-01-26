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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheClearCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('cache:clear')
			->setDescription('Clears cache, all or particular segment')
			->addArgument('segment', InputArgument::OPTIONAL, 'Segment to delete');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$cacheDir = $this->container->getParameter('directories.cache');

		$segments = array();

		$segment = $input->getArgument('segment');

		if ($segment) {
			$segments[] = $segment;
		} else {
			$segments = array_map(function ($value) {
				return basename($value);
			}, glob($cacheDir . '/*'));
		}

		foreach ($segments as $segment) {
			if (!is_dir($cacheDir . DIRECTORY_SEPARATOR . $segment)) {
				throw new \Exception(sprintf('Cache segment "%s" does not exist', $segment));
			}

			$output->writeln(sprintf('Deleting segment <info>%s</info>...', $segment));

			$this->rmDir($cacheDir . DIRECTORY_SEPARATOR . $segment);
		}
	}

	protected function rmDir($dir)
	{
		if (!is_dir($dir)) {
			throw new \Exception(sprintf('Argument "%s" is not a directory', $dir));
		}

		foreach(glob($dir . '/*') as $item) {
			if (is_dir($item)) {
				$this->rmDir($item);
			} else {
				unlink($item);
			}
		}

		rmdir($dir);
	}
}
