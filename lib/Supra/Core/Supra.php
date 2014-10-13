<?php

namespace Supra\Core;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Supra\Core\Configuration\Exception\ReferenceException;
use Supra\Core\Configuration\UniversalConfigLoader;
use Supra\Core\Controller\ExceptionController;
use Supra\Core\DependencyInjection\Container;
use Supra\Core\DependencyInjection\ContainerBuilder;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Doctrine\Logger\DebugLogger;
use Supra\Core\Kernel\HttpKernel;
use Supra\Core\Package\SupraPackageInterface;
use Supra\Core\Routing\Router;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Response;

abstract class  Supra extends ContainerBuilder
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

	/**
	 * @var array
	 */
	protected $overrides;

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
		$this->container = $container = new Container();

		$this->buildDirectories();

		$container->setParameter('environment', $this->environment);
		$container->setParameter('debug', $this->debug);
		$container['application'] = $this;

		//routing configuration
		$container['config.universal_loader'] = new UniversalConfigLoader();
		$container['routing.router'] = new Router();
		$container['kernel.kernel'] = function ($container) {
			return new HttpKernel();
		};
		$container['exception.controller'] = function () {
			return new ExceptionController();
		};

		//internal services
		//this actually must be based upon some config and there should be an option to override everything
		$this->buildHttpFoundation($container);
		$this->buildCache($container);
		$this->buildLogger($container);
		$this->buildEvents($container);
		$this->buildCli($container);
		$this->buildTemplating($container);
		$this->buildApplications($container);

		//package configuration
		$this->injectPackages($container);

		//configuration processing
		$this->buildConfiguration($container);

		//last pass to change something or created services based on finished configuration
		$this->finish($container);

		return $container;
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

	/**
	 * Basically, parses main supra config
	 *
	 * @return array
	 */
	public function getConfigurationOverrides()
	{
		if ($this->overrides) {
			return $this->overrides;
		}

		return $this->overrides = $this->container['config.universal_loader']->load($this->getSupraRoot().'/config.yml'); //@todo: resolve config per environment

	}

	/**
	 * Adds configuration section for later processing
	 *
	 * @param SupraPackageInterface $package
	 * @param $data
	 */
	public function addConfigurationSection(SupraPackageInterface $package, $data)
	{
		$overrides = $this->getConfigurationOverrides();

		if (isset($overrides[$package->getName()])) {
			$processor = new Processor();

			$data = $processor->processConfiguration($package->getConfiguration(), array(
				$data,
				$overrides[$package->getName()]
			));
		}

		$this->configurationSections[$package->getName()] = array(
			'package' => $package,
			'data' => $data
		);

		return $data;
	}

	/**
	 * Sets/overrides configuration section if corresponding package is defined
	 *
	 * @param $package
	 * @param $data
	 * @throws \Exception
	 * @internal param $name
	 */
	public function setConfigurationSection($package, $data)
	{
		if (!isset($this->configurationSections[$package])) {
			throw new \Exception(sprintf('There is no configuration section for package "%s"', $package));
		}

		$this->configurationSections[$package]['data'] = $data;
	}

	/**
	 * Gets configuration section data by package name, if defined
	 *
	 * @param $package
	 * @throws \Exception
	 */
	public function getConfigurationSection($package)
	{
		if (!isset($this->configurationSections[$package])) {
			throw new \Exception(sprintf('There is no configuration section for package "%s"', $package));
		}

		return $this->configurationSections[$package]['data'];
	}

	/**
	 * Returns wwwroot. Hardcode currently
	 *
	 * @todo: move this to container params
	 * @return string
	 */
	public function getWebRoot()
	{
		return $this->container->getParameter('directories.web');
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
	 * Sets up package directories contents
	 */
	protected function buildDirectories()
	{
		$reflection = new \ReflectionClass($this);

		$this->container->setParameter('directories.supra_root', dirname($reflection->getFileName()));
		$this->container->setParameter('directories.project_root', dirname($this->container->getParameter('directories.supra_root')));
		$this->container->setParameter('directories.storage',
			$this->container->getParameter('directories.project_root') .
			DIRECTORY_SEPARATOR .
			'storage'
		);
		$this->container->setParameter('directories.cache',
			$this->container->getParameter('directories.storage') .
			DIRECTORY_SEPARATOR .
			'cache'
		);
		$this->container->setParameter('directories.web',
			$this->container->getParameter('directories.project_root') .
			DIRECTORY_SEPARATOR .
			'web'
		);
		$this->container->setParameter('directories.public',
			$this->container->getParameter('directories.web') .
			DIRECTORY_SEPARATOR .
			'public'
		);
	}

	/**
	 * @todo: move to framwrok config, as always
	 * @param ContainerInterface $container
	 */
	protected function buildLogger(ContainerInterface $container)
	{
		$container['logger.doctrine'] = function (ContainerInterface $container) {
			return new DebugLogger();
		};

		$container['logger.logger'] = function (ContainerInterface $container) {
			$logger = new Logger('supra');

			if ($container->getParameter('debug')) {
				$sqlLogger = new DebugLogger();
				$sqlLogger->setContainer($container);

				$logger->pushHandler(new StreamHandler(
					$container->getParameter('directories.storage').DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR.
						$container->getParameter('environment').'.log',
					Logger::DEBUG
				));
			} else {
				$logger->pushHandler(new StreamHandler(
					$container->getParameter('directories.storage').DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR.
					$container->getParameter('environment').'.log',
					Logger::ERROR
				));
			}

			return $logger;
		};
	}

	/**
	 * Compiles configuration processing %foobar% placeholders
	 *
	 * @param ContainerInterface $container
	 * @throws \LogicException
	 */
	protected function buildConfiguration(ContainerInterface $container)
	{
		$config = array();

		$processor = new Processor();

		$configurationOverride = $this->getConfigurationOverrides();

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
				$chunks = explode('.', $parameter);

				$name = $chunks[0].'.'.$chunks[1];

				if (!isset($config[$name])) {
					throw new ReferenceException(sprintf('Parameter "%s" can not be resolved', $name));
				}

				$val = $config[$name];

				if (count($chunks) > 2) {
					$path = array_slice($chunks, 2);

					while ($key = array_shift($path)) {
						if (!array_key_exists($key, $val)) {
							throw new ReferenceException(sprintf('Lost at sub-key "%s" for parameter "%s"', $key, $parameter));
						}

						$val = $val[$key];
					}
				}

				$replacements[$expression[0]] = $val;
			}

			$value = strtr($value, $replacements);
		});

		foreach ($config as $key => $value) {
			$container->setParameter($key, $value);
		}

		//create services defined by 'services:' section
		foreach ($this->configurationSections as $key => $definition) {
			if (isset($config[$key.'.services'])) {
				foreach ($config[$key.'.services'] as $id => $serviceDefinition) {
					$container[$id] = function ($container) use ($serviceDefinition) {
						//this is where the magic happens
						$className = $serviceDefinition['class'];
						$parameters = $serviceDefinition['parameters'];

						$reflection = new \ReflectionClass($className);

						$instance = $reflection->newInstanceArgs($parameters);

						return $instance;
					};
				}
			}
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
