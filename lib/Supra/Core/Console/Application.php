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

namespace Supra\Core\Console;

use Supra\Core\DependencyInjection\Container;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Supra\Core\Console\AbstractCommand as SupraCommand;
use Symfony\Component\Console\Input\InputOption;

class Application extends BaseApplication implements ContainerAware
{

	protected $container;

	protected function getDefaultInputDefinition()
	{
		$definition = parent::getDefaultInputDefinition();

		$definition->addOptions(array(
			new InputOption('--env', '-e', InputOption::VALUE_NONE, 'Environment to use.'),
			new InputOption('--debug', null, InputOption::VALUE_NONE, 'Debug, set to zero to disable.'),
		));

		return $definition;
	}


	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function add(Command $command)
	{
		if (!$command instanceof SupraCommand && !$command instanceof ContainerAware &&
			!($command instanceof ListCommand || $command instanceof HelpCommand)
		) {
			throw new \InvalidArgumentException('All commands must extend Supra\Core\Console\AbstractCommand (or implement Supra\Core\DependencyInjection\ContainerAware)');
		}

		if ($command instanceof ContainerAware) {
			$command->setContainer($this->container);
		}

		parent::add($command);
	}
}
