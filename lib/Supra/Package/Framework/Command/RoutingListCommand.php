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
use Symfony\Component\Routing\Route;

class RoutingListCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('framework:routing:list');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('<info>Defined routes:</info>');

		$table = new Table($output);

		$table->setHeaders(array('Name', 'Pattern', 'Controller', 'Frontend'));

		foreach ($this->listRoutes($this->container->getRouter()->getRouteCollection()) as $route) {
			$table->addRow(array(
				$route['name'],
				$route['pattern'],
				$route['controller'],
				$route['frontend'] ? '<info>Yes</info>' : 'No'
			));
		}

		$table->render();
	}

	protected function listRoutes($routes)
	{
		$displayRoutes = array();

		foreach ($routes as $name => $route) {
			if ($route instanceof Route) {
				$displayRoutes[] = array(
					'name' => $name,
					'pattern' => $route->getPattern(),
					'controller' => $route->getDefault('controller'),
					'frontend' => $route->getOption('frontend')
				);
			} else {
				throw new \Exception('Only Route routes are supported now');
			}
		}

		return $displayRoutes;
	}

}
