<?php

namespace Supra\Core;

use Supra\Core\Configuration\Exception\ReferenceException;
use Supra\Core\Configuration\UniversalConfigLoader;
use Supra\Core\Controller\ExceptionController;
use Supra\Core\DependencyInjection\Container;
use Supra\Core\DependencyInjection\ContainerBuilder;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Kernel\HttpKernel;
use Supra\Core\Package\SupraPackageInterface;
use Supra\Core\Routing\Router;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Response;

abstract class Supra extends ContainerBuilder
{
	/**
	 * DI container
	 *
	 * @var \Supra\Core\DependencyInjection\ContainerInterface
	 */
	protected $container;

	/**
	 * Current environment, dev, prod, etc
	 *
	 * @var string
	 */
	protected $environment;

	/**
	 * Debug, enabled or disabled
	 *
	 * @var boolean
	 */
	protected $debug;

	protected $configPath = 'Resources/config';
	protected $viewPath = 'Resources/view';
	protected $publicPath = 'Resources/public';

	/**
	 * Accumulated configurations from packages
	 *
	 * @var array
	 */
	protected $configurationSections = array();

	public function __construct($environment = 'prod', $debug = false)
	{
		$this->environment = $environment;
		$this->debug = $debug;
		$this->packages = $this->registerPackages();

		if ($this->debug) {
			Debug::enable(-1, true);
		}
	}

	/**
	 * @return Response
	 */
	public function handleRequest()
	{
		return $this->container->getKernel()->handle($this->container->getRequest());
	}

	/**
	 * Parses controller name
	 * possible name formats:
	 *  - Full\Class\Name:action
	 *  - Package:Controller;action
	 * @param $name
	 * @return array
	 * @throws \Exception
	 */
	public function parseControllerName($name)
	{
		if (strpos($name, ':') === false) {
			throw new \Exception(sprintf('Unparsable controller name "%s", it should have at least one ":"', $name));
		}

		$parts = explode(':', $name);

		if (count($parts) == 2) {
			list($class, $action) = $parts;

			$action = $action.'Action';

			if (!class_exists($class)) {
				throw new \Exception(sprintf('Controller class "%s" not found', $class));
			}

			if (!method_exists($class, $action)) {
				throw new \Exception(sprintf('Controller method "%s" does not exist for controller class "%s"', $action, $class));
			}

			return array(
				'controller' => $class,
				'action' => $action
			);
		}

		if (count($parts) == 3) {
			list($package, $controller, $action) = $parts;

			$action = $action.'Action';

			$packageName = $this->resolvePackage($package);

			$parts = explode('\\', $packageName);

			array_pop($parts);

			$namespace = implode('\\', $parts);

			return array(
				'controller' => '\\'.$namespace.'\\Controller\\'.$controller.'Controller',
				'action' => $action
			);
		}

		throw new \Exception(sprintf('Too many chunks in your controller name (got "%s")', implode(', ', $parts)));
	}


	public function locateViewFile($package, $name)
	{
		if (is_string($package) && !class_exists($package)) {
			$package = $this->resolvePackage($package);
		}

		$path = $this->locatePackageRoot($package)
			. DIRECTORY_SEPARATOR . $this->viewPath
			. DIRECTORY_SEPARATOR . $name;

		if (!realpath($path) || !is_readable($path)) {
			throw new \Exception(
				sprintf('View file "%s" for package "%s" (%s) can not be resolved (expected location "%s")',
					$name, $this->formatName($package), $this->formatClass($package), $path
				)
			);
		}

		return $path;
	}

	public function locateConfigFile($package, $name)
	{
		$path = $this->locatePackageRoot($package)
			. DIRECTORY_SEPARATOR . $this->configPath
			. DIRECTORY_SEPARATOR . $name;

		if (!realpath($path) || !is_readable($path)) {
			throw new \Exception(
				sprintf('Config file "%s" for package "%s" (%s) can not be resolved (expected location "%s")',
					$name, $this->formatName($package), $this->formatClass($package), $path
				)
			);
		}

		return $path;
	}

	public function formatClass($package)
	{
		if (is_object($package)) {
			$package = get_class($package);
		}

		if (!class_exists($package)) {
			throw new \Exception(
				sprintf('Can not resolve package class name for reference "%s"',
					$package
				)
			);
		}

		return $package;
	}

	public function formatName($package)
	{
		$class = $this->formatClass($package);

		$class = explode('\\', $class);

		return $class[count($class) - 1];
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
		$container->setParameter('environment', $this->environment);
		$container->setParameter('debug', $this->debug);
		$container['application'] = $this;

		//routing configuration
		$container['config.universal_loader'] = new UniversalConfigLoader();
		$container['routing.router'] = new Router();
		$container['kernel'] = function ($container) {
			return new HttpKernel();
		};
		$container['exception.controller'] = function () {
			return new ExceptionController();
		};

		//internal services
		//this actually must be based upon some config and there should be an option to override everything
		$this->buildHttpFoundation($container);
		$this->buildCache($container);
		$this->buildEvents($container);
		$this->buildDoctrine($container);
		$this->buildCli($container);
		$this->buildSecurity($container);
		$this->buildTemplating($container);
		$this->buildApplications($container);
		$this->buildLocales($container);

		//package configuration
		$this->injectPackages($container);

		//configuration processing
		$this->buildConfiguration($container);

		//last pass to change something
		$this->finish($container);

		return $this->container = $container;
	}

	public function boot()
	{
		//boot packages
		foreach ($this->getPackages() as $package) {
			$package->setContainer($this->container);
			$package->boot();
		}
	}

	public function shutdown()
	{
		foreach ($this->getPackages() as $package) {
			$package->shutdown();
		}

		$this->container = null;
		$this->environment = null;
		$this->debug = null;
		$this->configurationSections = array();
	}


	public function addConfigurationSection(SupraPackageInterface $package, $data)
	{
		$this->configurationSections[$package->getName()] = array(
			'package' => $package,
			'data' => $data
		);
	}


	/**
	 * Returns wwwroot. Hardcode currently
	 *
	 * @todo: move this to container params
	 * @return string
	 */
	public function getWebRoot()
	{
		return realpath($this->getProjectRoot() . '/src/webroot');
	}


	/**
	 * This function uses woodo magic to extract short package name from className
	 *
	 * @param $package
	 */
	public function resolveName($package)
	{
		if (is_object($package)) {
			$package = get_class($package);
		}

		$package = substr($package, strrpos($package, '\\'));

		$package = trim($package, '\\');

		$package = str_replace(array('Supra', 'Package'), '', $package);

		return $package;
	}

	/**
	 * Compiles configuration processing %foobar% placeholders
	 *
	 * @param ContainerInterface $container
	 * @throws \LogicException
	 */
	protected function buildConfiguration(ContainerInterface $container)
	{
		$configurationOverride = $container['config.universal_loader']->load($this->getSupraRoot().'/config.yml'); //@todo: resolve config per environment

		$config = array();

		$processor = new Processor();

		foreach ($this->configurationSections as $key => $definition) {
			$package = $definition['package'];
			$data = $definition['data'];

			$configuration = $package->getConfiguration();

			$configs = array();

			$configs[] = $data;

			if (array_key_exists($key, $configurationOverride)) {
				$configs[] = $configurationOverride[$key];
				unset($configurationOverride[$key]);
			}

			$packageConfiguration = $processor->processConfiguration($configuration, $configs);

			foreach ($packageConfiguration as $confKey => $value) {
				$config[$key.'.'.$confKey] = $value;
			}
		}

		if (count($configurationOverride) != 0) {
			throw new \LogicException(
				sprintf('Extra keys are found in Supra\'s config.yml that do not belong to any package: %s.',
					implode(', ', array_keys($configurationOverride)))
			);
		}

		array_walk_recursive($config, function (&$value) use (&$config) {
			if (!is_string($value)) {
				return;
			}

			$count = preg_match_all('/%[a-z\\._]+%/i', $value, $matches);

			if (!$count) {
				return;
			}

			$replacements = array();

			foreach ($matches as $expression) {
				$parameter = trim($expression[0], '%');
				if (!isset($config[$parameter])) {
					throw new ReferenceException('Parameter "%s" can not be resolved', $parameter);
				}
				$replacements[$expression[0]] = $config[$parameter];
			}

			$value = strtr($value, $replacements);
		});

		foreach ($config as $key => $value) {
			$container->setParameter($key, $value);
		}
	}

	/**
	 * Registers packages, should return an array of package instances
	 *
	 * @return array
	 */
	protected function registerPackages()
	{
		return array();
	}

	/**
	 * Allows packages to do some changes after the configuration has been built
	 *
	 * @param ContainerInterface $container
	 */
	protected function finish(ContainerInterface $container)
	{
		foreach ($this->getPackages() as $package) {
			$package->finish($container);
		}
	}


	/**
	 * Injects packages into a container
	 *
	 * @param ContainerInterface $container
	 */
	protected function injectPackages(ContainerInterface $container)
	{
		foreach ($this->getPackages() as $package) {
			$package->inject($container);
		}
	}
}
