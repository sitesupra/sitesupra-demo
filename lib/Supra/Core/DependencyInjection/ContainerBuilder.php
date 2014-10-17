<?php

namespace Supra\Core\DependencyInjection;

use Doctrine\DBAL\Driver\PDOMySql;
use Supra\Core\Application\ApplicationManager;
use Supra\Core\Cache\Cache;
use Supra\Core\Cache\Driver\File;
use Supra\Core\Console\Application;
use Supra\Core\Event\TraceableEventDispatcher;
use Supra\Core\Templating\Templating;
use Supra\Package\Framework\Twig\SupraGlobal;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

abstract class ContainerBuilder
{

	/**
	 * Array of instantiated packages
	 *
	 * @var array
	 */
	protected $packages;

	public function locatePublicFolder($package)
	{
 		if (is_string($package) && !class_exists($package)) {
 			$package = $this->resolvePackage($package);
		}

 		return $this->locatePackageRoot($package)
 			. DIRECTORY_SEPARATOR . $this->publicPath;
 	}

	public function locatePackageRoot($package)
	{
		if (is_string($package) && !class_exists($package)) {
			$package = $this->resolvePackage($package);
		}

		$reflection = new \ReflectionClass($package);

		return dirname($reflection->getFileName());
	}

	/**
	 * @return \Supra\Core\Package\AbstractSupraPackage[]
	 */
	public function getPackages()
	{
		return $this->packages;
	}

	public function buildHttpFoundation(ContainerInterface $container)
	{
		$container['http.request'] = function () {
			return Request::createFromGlobals();
		};
	}

	/**
	 * Returns root of SupraSomething extends Supra file
	 *
	 * @return string
	 */
	public function getSupraRoot()
	{
		return $this->container->getParameter('directories.supra_root');
	}

	/**
	 * Returns  project root (core folder of supra installation)
	 *
	 * @return string
	 */
	public function getProjectRoot()
	{
		return $this->container->getParameter('directories.project_root');
	}

	/**
	 * This function uses woodoo magic to resolve package class name
	 *
	 * @param string $name
	 * @throws \Exception
	 * @return string
	 */
	public function resolvePackage($name)
	{
		$packages = $this->getPackages();

		//most commonly we have Foobar or SupraPackageFoobar, which maps directly to package class
		//the other options is "foobar", which is mapped to AbstractPackage::getName
		foreach ($packages as $instance) {
			$class = get_class($instance);
			$classParts = explode('\\', $class);
			$className = $classParts[count($classParts) - 1];

			if ($name == $className || 'SupraPackage' . $name == $className) {
				return $class;
			}

			if ($name == $instance->getName()) {
				return $class;
			}
		}

		throw new \Exception(sprintf('Package "%s" can not be resolved', $name));
	}

	public function buildApplications($container)
	{
		$container['applications.manager'] = function () {
			return new ApplicationManager();
		};
	}

	protected function buildCache(ContainerInterface $container)
	{
		$container['cache.driver'] = new File($container->getParameter('directories.cache'));
		$container['cache.cache'] = function (ContainerInterface $container) {
			$cache = new Cache();
			$cache->setContainer($container);
			$cache->setDriver($container['cache.driver']);

			return $cache;
		};
	}

	protected function buildEvents(ContainerInterface $container)
	{
		if ($container->getParameter('debug')) {
			$container['event.dispatcher'] = new TraceableEventDispatcher();
		} else {
			$container['event.dispatcher'] = new EventDispatcher();
		}
	}

	protected function buildCli(ContainerInterface $container)
	{
		$container['console.application'] = new Application();
	}

	/**
	 * Builds templating, currently only twig
	 *
	 * @param ContainerInterface $container
	 */
	protected function buildTemplating($container)
	{
		$container['templating.global'] = function () {
			return new SupraGlobal();
		};

		$container['templating.templating'] = function () use ($container) {
			$templating = new Templating();
			$templating->addGlobal('supra', $container['templating.global']);

			return $templating;
		};
	}
}
