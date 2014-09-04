<?php

namespace Supra\Core;

use Supra\Core\Configuration\UniversalConfigLoader;
use Supra\Core\Console\Application;
use Supra\Core\DependencyInjection\Container;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcher;

abstract class Supra
{
	/**
	 *
	 * @var array
	 */
	protected $packages;

	protected $container;

	protected function registerPackages()
	{
		return array();
	}

	public function __construct()
	{
		$this->packages = $this->registerPackages();
	}

	/**
	 *
	 * @return Container
	 */
	public function buildContainer()
	{
		if ($this->container) {
			return $this->container;
		}

		//getting container instance, configuring services
		$container = new Container();
		$container['application'] = $this;

		//routing configuration
		$container['config.universal_loader'] = new UniversalConfigLoader();
		$container['routing.router'] = new Router();

		$this->buildEvents($container);
		$this->buildCli($container);
		$this->buildSecurity($container);

		$this->injectPackages($container);

		return $this->container = $container;
	}

	public function boot()
	{
		//boot packages
		foreach ($this->getPackages() as $package)
		{
			$package->setContainer($this->container);
			$package->boot();
		}
	}

	/**
	 * @return \Supra\Core\Package\AbstractSupraPackage[]
	 */
	public function getPackages()
	{
		return $this->packages;
	}

	protected function buildSecurity(ContainerInterface $containerInterface)
	{

	}

	protected function buildEvents(ContainerInterface $container)
	{
		$container['event.dispatcher'] = new EventDispatcher();
	}

	protected function buildCli(ContainerInterface $container)
	{
		$container['console.application'] = new Application();
	}

	protected function injectPackages($container)
	{
		foreach ($this->getPackages() as $package) {
			$package->inject($container);
		}
	}
}
