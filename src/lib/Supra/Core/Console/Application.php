<?php

namespace Supra\Core\Console;

use Supra\Core\DependencyInjection\Container;
use Supra\Core\DependencyInjection\ContainerAware;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Supra\Core\Console\AbstractCommand as SupraCommand;

class Application extends BaseApplication implements ContainerAware
{

	protected $container;

	public function setContainer(Container $container)
	{
		$this->container = $container;
	}

	public function add(Command $command)
	{
		if (!$command instanceof SupraCommand &&
			!($command instanceof ListCommand || $command instanceof HelpCommand)
		) {
			throw new \InvalidArgumentException('All commands must extends Supra\Core\Console\Command');
		}

		parent::add($command);
	}
}
