<?php

namespace Supra\Core\DependencyInjection;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOMySql;
use Doctrine\DBAL\Types\ArrayType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Doctrine\DBAL\Event\Listeners\MysqlSessionInit;
use Supra\Package\Cms\Pages\Listener\VersionedEntitySchemaListener;
use Supra\Core\Application\ApplicationManager;
use Supra\Core\Cache\Cache;
use Supra\Core\Cache\Driver\File;
use Supra\Core\Console\Application;
use Supra\Core\Doctrine\ManagerRegistry;
use Supra\Core\Doctrine\Subscriber\TableNamePrefixer;
use Supra\Core\Doctrine\Type\PathType;
use Supra\Core\Doctrine\Type\SupraIdType;
use Supra\Core\Templating\Templating;
use Supra\Database\DetachedDiscriminatorHandler;
use Supra\NestedSet\Listener\NestedSetListener;
use Supra\Package\CmsAuthentication\Encoder\SupraBlowfishEncoder;
use Supra\Package\Framework\Twig\SupraGlobal;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager;
use Symfony\Component\Security\Core\Authentication\Provider\AnonymousAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\User\ChainUserProvider;
use Symfony\Component\Security\Core\User\UserChecker;

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

		$container['http.session'] = function ($container) {
			if (PHP_SAPI == 'cli') {
				throw new \Exception('Sessions are not possible in CLI mode');
			}

			$session = new Session();
			$session->start();

			$container['http.request']->setSession($session);

			return $session;
		};
	}

	public function buildDoctrine(ContainerInterface $container)
	{
		//event manager
		$container['doctrine.event_manager.public'] = function (ContainerInterface $container) {
			$eventManager = new EventManager();
			//for later porting
			// Adds prefix for tables
			//@todo: move to config
			$eventManager->addEventSubscriber(new MysqlSessionInit('utf8'));
			$eventManager->addEventSubscriber(new TableNamePrefixer('su_', ''));

			/*// Updates creation and modification timestamps for appropriate entities
			$eventManager->addEventSubscriber(new TimestampableListener());

			// Maps revision property for appropriate entities
			$eventManager->addEventSubscriber(new Listener\EntityRevisionFieldMapperListener());

			// Drops file storage cache group when files are being changed
			$eventManager->addEventSubscriber(new FileGroupCacheDropListener());

			$eventManager->addEventListener(array(Events::loadClassMetadata), new DetachedDiscriminatorHandler());

			foreach ($this->eventSubscribers as $eventSubscriber) {
				if (is_string($eventSubscriber)) {
					$eventSubscriber = Loader::getClassInstance($eventSubscriber, 'Doctrine\Common\EventSubscriber');
				}
				$eventManager->addEventSubscriber($eventSubscriber);
			}*/

			$eventManager->addEventSubscriber(new DetachedDiscriminatorHandler());
			$eventManager->addEventSubscriber(new NestedSetListener());

			return $eventManager;
		};

		$container['doctrine.event_manager.cms'] = function (ContainerInterface $container) {
			$eventManager = new EventManager();

			$eventManager->addEventSubscriber(new MysqlSessionInit('utf8'));
			$eventManager->addEventSubscriber(new TableNamePrefixer('su_', ''));

			$eventManager->addEventSubscriber(new DetachedDiscriminatorHandler());
			$eventManager->addEventSubscriber(new NestedSetListener());

			$eventManager->addEventSubscriber(new VersionedEntitySchemaListener());

			return $eventManager;
		};

		$container['doctrine.orm_configuration'] = function (ContainerInterface $container) {
			//loading package directories
			$packages = $this->getPackages();

			$paths = array();

			foreach ($packages as $package) {
				$entityDir = $this->locatePackageRoot($package) . DIRECTORY_SEPARATOR . 'Entity';

				if (is_dir($entityDir)) {
					$paths[] = $entityDir;
				}
			}

			$configuration = Setup::createAnnotationMetadataConfiguration($paths,
				$container->getParameter('debug'),
				//todo: use general supra path
				sys_get_temp_dir(),
				//todo: configure cache with config
				null
			);

			//Foo:Bar -> \FooPackage\Entity\Bar aliases
			foreach ($packages as $package) {
				$class = get_class($package);
				$namespace = substr($class, 0, strrpos($class, '\\')) . '\\Entity';
				$configuration->addEntityNamespace($this->resolveName($package), $namespace);
			}

			//custom types
			Type::addType(SupraIdType::NAME, SupraIdType::CN);
			Type::addType(PathType::NAME, PathType::CN);
			Type::overrideType(ArrayType::TARRAY, '\Supra\Core\Doctrine\Type\ArrayType');

			return $configuration;
		};

		//connections
		//@todo: move to configuration
		$container['doctrine.connections.default'] = function (ContainerInterface $container) {
			$connection = new Connection(
				array(
					'host' => 'localhost',
					'user' => 'root',
					'password' => '',
					'dbname' => 'supra9'
				),
				new PDOMySql\Driver(),
				$container['doctrine.orm_configuration'],
				$container['doctrine.event_manager.public']
			);

			return $connection;
		};

		$container['doctrine.connections.shared'] = function (ContainerInterface $container) {
			$connection = new Connection(
				array(
					'host' => 'localhost',
					'user' => 'root',
					'password' => '',
					'dbname' => 'supra9_shared_users'
				),
				new PDOMySql\Driver(),
				$container['doctrine.orm_configuration'],
				$container['doctrine.event_manager.public']
			);

			return $connection;
		};

		$container['doctrine.connections.cms'] = function (ContainerInterface $container) {
			$connection = new Connection(
				array(
					'host' => 'localhost',
					'user' => 'root',
					'password' => 'root',
					'dbname' => 'supra7'
				),
				new PDOMySql\Driver(),
				$container['doctrine.orm_configuration'],
				$container['doctrine.event_manager.cms']
			);

			return $connection;
		};

		//supra-specific entity managers
		$container['doctrine.entity_managers.public'] = function (ContainerInterface $container) {
			return EntityManager::create(
				$container['doctrine.connections.default'],
				$container['doctrine.orm_configuration'],
				$container['doctrine.event_manager.public']
			);
		};

		$container['doctrine.entity_managers.cms'] = function (ContainerInterface $container) {
			return EntityManager::create(
				$container['doctrine.connections.cms'],
				$container['doctrine.orm_configuration'],
				$container['doctrine.event_manager.cms']
			);
		};

		$container['doctrine.entity_managers.shared'] = function (ContainerInterface $container) {
			return EntityManager::create(
				$container['doctrine.connections.shared'],
				$container['doctrine.orm_configuration'],
				$container['doctrine.event_manager.public']
			);
		};

		$container['doctrine.entity_managers'] = function (ContainerInterface $container) {
			return array(
				'public' => 'doctrine.entity_managers.public',
				'cms' => 'doctrine.entity_managers.cms',
				'shared' => 'doctrine.entity_managers.shared',
			);
		};

		//@todo: refactor this much
		$container['doctrine.doctrine'] = function (ContainerInterface $container) {
			return new ManagerRegistry(
				'supra.doctrine',
				array(
					'default' => 'doctrine.connections.default',
					'shared' => 'doctrine.connections.shared'
				),
				$container['doctrine.entity_managers'],
				'default',
				'public',
				'foobar'
			);
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
		$container['cache.driver'] = new File($this->getProjectRoot() . DIRECTORY_SEPARATOR . 'cache');
		$container['cache.cache'] = function (ContainerInterface $container) {
			$cache = new Cache();
			$cache->setContainer($container);
			$cache->setDriver($container['cache.driver']);

			return $cache;
		};
	}

	protected function buildSecurity(ContainerInterface $container)
	{
		$container['security.user_providers'] = function (ContainerInterface $container) {
			return array(
				$container['doctrine.entity_managers.public']->getRepository('CmsAuthentication:User'),
				$container['doctrine.entity_managers.shared']->getRepository('CmsAuthentication:User')
			);
		};

		$container['security.user_provider'] = function (ContainerInterface $container) {
			return new ChainUserProvider($container['security.user_providers']);
		};

		$container->setParameter('security.provider_key', 'cms_authentication');

		$userChecker = new UserChecker();

		//@todo: this should be moved to config
		$encoderFactory = new EncoderFactory(
			array(
				'Supra\Package\CmsAuthentication\Entity\User' => new SupraBlowfishEncoder()
			)
		);

		$providers = array(
			new AnonymousAuthenticationProvider(uniqid()),
			new DaoAuthenticationProvider(
				$container['security.user_provider'],
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
}
