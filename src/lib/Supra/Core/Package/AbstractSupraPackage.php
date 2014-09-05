<?php

namespace Supra\Core\Package;

use Doctrine\Common\Util\Inflector;
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

	/**
	 * Underscore name of a package
	 *
	 * @var string
	 */
	protected $name;

	public function __construct()
	{
		$this->name = $this->getName();
	}

	/**
	 * Creates a name for a command that can be used throughout configuration files
	 *
	 * @return string
	 */
	public function getName()
	{
		$class = get_class($this);

		$class = explode('\\', $class);

		$class = $class[count($class) - 1];

		$class = str_replace(array('Supra', 'Package'), '', $class);

		$inflector = new Inflector();
		$name = $inflector->tableize($class);

		return $name;
	}

	public function boot()
	{
	}

	public function inject(ContainerInterface $container)
	{
	}

	public function finish(ContainerInterface $container)
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
