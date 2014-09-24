<?php

namespace Supra\Core\Console;

use Supra\Core\DependencyInjection\Container;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Supra\Core\Console\AbstractCommand as SupraCommand;

class Application extends BaseApplication implements ContainerAware
{

	protected $container;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function add(Command $command)
	{
		if (!$command instanceof SupraCommand && !$command instanceof ContainerAware &&
			!($command instanceof ListCommand || $command instanceof HelpCommand)
		) {
			throw new \InvalidArgumentException('All commands must extend Supra\Core\Console\Command (or implement Supra\Core\DependencyInjection\ContainerAware)');
		}

		if ($command instanceof ContainerAware) {
			$command->setContainer($this->container);
		}

		parent::add($command);
	}
}
