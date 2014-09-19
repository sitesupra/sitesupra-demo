<?php

namespace Supra\Core;

use Supra\Core\Locale\Detector\CookieDetector;
use Supra\Core\Locale\Detector\PathLocaleDetector;
use Supra\Core\Application\ApplicationManager;
use Supra\Core\Cache\Cache;
use Supra\Core\Cache\Driver\File;
use Supra\Core\Configuration\Exception\ReferenceException;
use Supra\Core\Configuration\UniversalConfigLoader;
use Supra\Core\Console\Application;
use Supra\Core\DependencyInjection\Container;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Locale\Locale;
use Supra\Core\Locale\LocaleManager;
use Supra\Core\Locale\Storage\CookieStorage;
use Supra\Core\Package\PackageLocator;
use Supra\Core\Package\SupraPackageInterface;
use Supra\Core\Routing\Router;
use Supra\Core\Templating\Templating;
use Supra\Package\Framework\Twig\SupraGlobal;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\Security\Core\Authentication\Provider\AnonymousAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\User\ChainUserProvider;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;
use Symfony\Component\Security\Core\User\UserChecker;

abstract class Supra
{
	/**
	 *
	 * @var array
	 */
	protected $packages;

	/**
	 * @var \Supra\Core\DependencyInjection\ContainerInterface
	 */
	protected $container;

	/**
	 * Accumulated configurations from packages
	 *
	 * @var array
	 */
	protected $configurationSections = array();

	public function __construct()
	{
		PackageLocator::setSupra($this);
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

		//internal services
		//this actually must be based upon some config and there should be an option to override everything
		$this->buildHttpFoundation($container);
		$this->buildCache($container);
		$this->buildEvents($container);
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

	public function addConfigurationSection(SupraPackageInterface $package, $data)
	{
		$this->configurationSections[$package->getName()] = array(
			'package' => $package,
			'data' => $data
		);
	}

	public function buildHttpFoundation(ContainerInterface $container)
	{
		$container['http.request'] = function () {
			return Request::createFromGlobals();
		};

		$container['http.session'] = function ($container) {
			$session = new Session();
			$session->start();

			$container['http.request']->setSession($session);

			return $session;
		};
	}

	public function buildLocales(ContainerInterface $container)
	{
		//@todo: this should be refactored to some +- sane locale storage, preferably in the database

		$container['locale.detector.path'] = function () {
			return new PathLocaleDetector();
		};

		$container['locale.detector.cookie'] = function () {
			return new CookieDetector();
		};

		$container['locale.storage.cookie'] = function () {
			return new CookieStorage();
		};

		$container['locale.manager'] = function (ContainerInterface $container) {
			$localeManager = new LocaleManager();

			/* English | Latvia */
			$locale = new Locale();
			$locale->setId('en_LV');
			$locale->setTitle('English');
			$locale->setCountry('Latvia');
			$locale->addProperty('flag', 'gb');
			$locale->setActive(false);
			$locale->addProperty('language', 'en'); // as per ISO 639-1
			$localeManager->add($locale);

			/* Latvian | Latvia */
			$locale = new Locale();
			$locale->setId('lv_LV');
			$locale->setTitle('Latvian');
			$locale->setCountry('Latvia');
			$locale->addProperty('flag', 'lv');
			$locale->addProperty('language', 'lv'); // as per ISO 639-1
			$localeManager->add($locale);

			/* Russian | Russia */
			$locale = new Locale();
			$locale->setId('ru_RU');
			$locale->setTitle('Russian');
			$locale->setCountry('Russia');
			$locale->addProperty('flag', 'ru');
			$locale->addProperty('language', 'ru'); // as per ISO 639-1
			$localeManager->add($locale);

			$localeManager->setCurrent('lv_LV');

			$localeManager->addDetector($container['locale.detector.path']);
			$localeManager->addDetector($container['locale.detector.cookie']);

			$localeManager->addStorage($container['locale.storage.cookie']);

			return $localeManager;
		};
	}

	/**
	 * Returns root of SupraSomething extends Supra file
	 *
	 * @todo: move to container params
	 * @return string
	 */
	public function getSupraRoot()
	{
		$reflection = new \ReflectionClass($this);

		return dirname($reflection->getFileName());
	}

	/**
	 * Returns  project root (core folder of supra installation)
	 *
	 * @todo: move this to container params
	 * @return string
	 */
	public function getProjectRoot()
	{
		return dirname($this->getSupraRoot());
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
	 * This function uses woodoo magic to resolve package name
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

			if ($name == $className || 'SupraPackage'.$name == $className) {
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

	protected function buildCache(ContainerInterface $container)
	{
		$container['cache.driver'] = new File($this->getProjectRoot() . DIRECTORY_SEPARATOR . 'cache');
		$container['cache.cache'] = function(ContainerInterface $container) {
			$cache = new Cache();
			$cache->setContainer($container);
			$cache->setDriver($container['cache.driver']);

			return $cache;
		};
	}

	protected function buildSecurity(ContainerInterface $container)
	{
		$userProvider = new ChainUserProvider(
			array(
				new InMemoryUserProvider(array(
					'admin' => array('password' => 'admin')
				))
			)
		);

		$container->setParameter('security.provider_key', 'cms_authentication');

		$userChecker = new UserChecker();

		//@todo: this should be moved to config
		$encoderFactory = new EncoderFactory(
			array(
				'Symfony\Component\Security\Core\User\User' => new PlaintextPasswordEncoder()
			)
		);

		$providers = array(
			new AnonymousAuthenticationProvider(uniqid()),
			new DaoAuthenticationProvider(
				$userProvider,
				$userChecker,
				$container->getParameter('security.provider_key'),
				$encoderFactory
			)
		);

		$container['security.authentication_manager'] = function () use ($providers) {
			return new AuthenticationProviderManager($providers);
		};

		$container['security.voters'] = function () {
			return array(new RoleVoter()); //@todo: this should be refactored to acls
		};

		$container['security.access_decision_manager'] = function ($container) {
			return new AccessDecisionManager($container['security.voters']);
		};

		$container['security.context'] = function ($container) {
			return new SecurityContext(
				$container['security.authentication_manager'],
				$container['security.access_decision_manager']
			);
		};
	}

	protected function buildEvents(ContainerInterface $container)
	{
		$container['event.dispatcher'] = new EventDispatcher();
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
