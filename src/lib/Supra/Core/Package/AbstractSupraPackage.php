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

	public function getConfiguration()
	{
		$class = explode('\\', get_class($this));

		$className = array_pop($class);

		array_push($class, 'Configuration');
		array_push($class, $className.'Configuration');

		$class = '\\'.implode('\\', $class);

		return new $class();
	}

	public function loadConfiguration(ContainerInterface $container, $file = 'config.yml')
	{
		$file = $container->getApplication()->locateConfigFile($this, $file);

		$data = $container['config.universal_loader']->load($file);

		return $container->getApplication()->addConfigurationSection($this, $data);
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
