<?php

namespace Supra\Core\Console;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;

abstract class AbstractCommand extends BaseCommand implements ContainerAware
{
	/**
	 * @var \Supra\Core\DependencyInjection\ContainerInterface
	 */
	protected $container;

	/**
	 * @param \Supra\Core\DependencyInjection\ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

}