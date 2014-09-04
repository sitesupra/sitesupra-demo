<?php

namespace Supra\Core;

use Supra\Core\Configuration\UniversalConfigLoader;
use Supra\Core\Console\Application;
use Supra\Core\DependencyInjection\Container;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcher;
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

	protected function buildSecurity(ContainerInterface $container)
	{
		$userProvider = new ChainUserProvider(
			array(
				new InMemoryUserProvider()
			)
		);

		$userChecker = new UserChecker();

		//@todo: this should be moved to config
		$encoderFactory = new EncoderFactory(
			array(
				'\Symfony\Component\Security\Core\User' => new PlaintextPasswordEncoder()
			)
		);

		$providers = array(
			new AnonymousAuthenticationProvider(uniqid()),
			new DaoAuthenticationProvider(
				$userProvider,
				$userChecker,
				'cms_authentication',
				$encoderFactory
			)
		);

		$authenticationManager = new AuthenticationProviderManager($providers);

		$roleVoter = new RoleVoter(); //@todo: this should be refactored to acls

		$accessDecisionManager = new AccessDecisionManager(array($roleVoter));

		$container['security.context'] = new SecurityContext($authenticationManager, $accessDecisionManager);
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
