<?php

namespace Supra\Core\Package;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;

abstract class AbstractSupraPackage implements SupraPackageInterface, ContainerAware
{
	/**
	 * Dependency injection container
	 *
	 * @var \Supra\Core\DependencyInjection\ContainerInterface
	 */
	protected $container;

	public function boot()
	{

	}

	public function inject(ContainerInterface $container)
	{

	}

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function shutdown()
	{

	}

}
