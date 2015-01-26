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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheListCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('cache:list')
			->setDescription('Dumps cache usage');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$cacheDir = $this->container->getParameter('directories.cache');

		$table = new Table($output);

		$table->setHeaders(array('Directory', 'Size', 'Files'));

		foreach (glob($cacheDir.'/*') as $dir) {
			list($size, $count) = $this->dirMetrics($dir);
			$table->addRow(array(
				basename($dir),
				sprintf('%.2fM', $size / 1024 / 1024),
				$count
			));
		}

		$table->render();
	}

	protected function dirMetrics($dir)
	{
		$size = $count = 0;

		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
			$count ++;
			$size += $file->getSize();
		}

		return array($size, $count);
	}
}
