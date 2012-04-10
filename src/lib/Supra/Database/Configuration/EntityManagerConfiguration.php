<?php

namespace Supra\Database\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Supra\Database\Doctrine;
use Supra\Cache\CacheNamespaceWrapper;
use Doctrine\Common\Cache\ArrayCache;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\Events;
use Doctrine\Common\EventManager;
use Supra\NestedSet\Listener\NestedSetListener;
use Supra\Database\Doctrine\Listener\TableNamePrefixer;
use Supra\Database\Doctrine\Hydrator\ColumnHydrator;
use Supra\Controller\Pages\Listener;
use Doctrine\DBAL\Types\Type;
use Supra\Database\Doctrine\Type\SupraIdType;
use Supra\Database\Doctrine\Type\PathType;
use Supra\Database\Doctrine\Listener\TimestampableListener;
use Supra\Controller\Pages\PageController;
use Supra\FileStorage\Listener\FileGroupCacheDropListener;
use PDO;
use Supra\Log\Logger\EventsSqlLogger;
use Supra\Database\Doctrine\Type\UtcDateTimeType;
use Supra\Database\DetachedDiscriminatorHandler;
use Doctrine\Common\Annotations\AnnotationRegistry;

/**
 * Entity manager creation
 */
class EntityManagerConfiguration implements ConfigurationInterface
{

	protected static $staticConfigurationDone = false;

	/**
	 * Entity manager identificator
	 * @var string
	 */
	public $name;

	/**
	 * Namespaces to bind the created entity manager to the object repository
	 * @var array
	 */
	public $objectRepositoryBindings = array();

	/**
	 * @var string
	 */
	public $iniConfigurationSection = 'database';

	/**
	 * @var array
	 */
	public $entityLibraryPaths = array(
		'Supra/Controller/Pages/Entity/',
		'Supra/FileStorage/Entity/',
		'Supra/User/Entity/',
		'Supra/Console/Cron/Entity/',
		'Supra/Search/Entity',
		'Supra/BannerMachine/Entity',
		'Supra/Payment/Entity',
		'Supra/Mailer/MassMail/Entity',
		'Supra/Configuration/Entity',
		'Supra/Social/Facebook/Entity',
	);

	/**
	 * @var array
	 */
	public $entityComponentPaths = array(
	);

	/**
	 * @var array
	 */
	public $entityPaths = array(
	);

	/**
	 * @var string
	 */
	public $tableNamePrefix = 'su_';

	/**
	 * @var string
	 */
	public $tableNamePrefixNamespace = 'Supra';

	/**
	 * 
	 */
	public function configure()
	{
		if ( ! self::$staticConfigurationDone) {
			self::doStaticConfiguration();
			self::$staticConfigurationDone = true;
		}

		$this->getEntityManager();
	}

	/**
	 * One time execution
	 */
	final static function doStaticConfiguration()
	{
		AnnotationRegistry::registerAutoloadNamespace('Supra\Database\Annotation\\', array(SUPRA_LIBRARY_PATH));

		Type::addType(SupraIdType::NAME, SupraIdType::CN);
		Type::addType(PathType::NAME, PathType::CN);
		Type::overrideType(Type::DATETIME, UtcDateTimeType::CN);

		// TODO: Remove later
		Type::addType('block', 'Supra\Database\Doctrine\Type\UnknownType');
		Type::addType('sha1', 'Supra\Database\Doctrine\Type\UnknownType');
		Type::addType('template', 'Supra\Database\Doctrine\Type\UnknownType');
		Type::addType('supraId', 'Supra\Database\Doctrine\Type\UnknownType');
	}

	protected function getEntityManager()
	{
		$connectionOptions = $this->getConnectionOptions();
		$config = $this->getDoctrineConfiguration();
		$eventManager = $this->getEventManager();

		$entityManager = EntityManager::create($connectionOptions, $config, $eventManager);

		$this->configureEntityManager($entityManager);
		$this->bindEntityManagerInObjectRepository($entityManager);
	}

	protected function getConnectionOptions()
	{
		$ini = ObjectRepository::getIniConfigurationLoader($this);
		$connectionOptions = $ini->getSection($this->iniConfigurationSection);

		// It is not required on PHP 5.3.6 anymore
		$connectionOptions['driverOptions'] = array(
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
		);

		return $connectionOptions;
	}

	protected function getDoctrineConfiguration()
	{
		$config = new Configuration();

		$this->configureCache($config);
		$this->configureMetadataDriver($config);
		$this->configureProxy($config);
		$this->configureSqlLogger($config);
		$this->configureCustomFunctions($config);

		return $config;
	}

	protected function configureCache(Configuration $config)
	{
		$cacheInstance = ObjectRepository::getCacheAdapter($this);
		$cache = new CacheNamespaceWrapper($cacheInstance, $this->name);
		$config->setQueryCacheImpl($cache);
		$config->setResultCacheImpl($cache);

		// This will create proxy objects when entity metadata is saved in the cache
		$metadataCache = new Doctrine\Cache\ProxyFactoryMetadataCache($cache);
		$config->setMetadataCacheImpl($metadataCache);
	}

	protected function configureMetadataDriver(Configuration $config)
	{
		$entityPaths = $this->getEntityPaths();

		$driverImpl = $config->newDefaultAnnotationDriver($entityPaths);
		$config->setMetadataDriverImpl($driverImpl);
	}

	protected function getEntityPaths()
	{
		$entityPaths = array();

		foreach ($this->entityLibraryPaths as $path) {
			$entityPaths[] = SUPRA_LIBRARY_PATH . $path;
		}

		foreach ($this->entityComponentPaths as $path) {
			$entityPaths[] = SUPRA_COMPONENT_PATH . $path;
		}

		foreach ($this->entityPaths as $path) {
			$entityPaths[] = $path;
		}

		return $entityPaths;
	}

	protected function configureProxy(Configuration $config)
	{
		// Proxy configuration
		$config->setProxyDir(SUPRA_LIBRARY_PATH . 'Supra/Proxy/');
		$config->setProxyNamespace('Supra\\Proxy');
		$config->setAutoGenerateProxyClasses(false);
	}

	protected function configureSqlLogger(Configuration $config)
	{
		$sqlLogger = new EventsSqlLogger();
		$config->setSQLLogger($sqlLogger);
	}

	protected function configureCustomFunctions(Configuration $config)
	{
		// required by nested set functionality
		$config->addCustomNumericFunction('IF', 'Supra\Database\Doctrine\Functions\IfFunction');
	}

	protected function getEventManager()
	{
		$eventManager = $this->createEventManager();
		$this->configureEventManager($eventManager);

		return $eventManager;
	}

	protected function createEventManager()
	{
		$eventManager = new EventManager();

		return $eventManager;
	}

	protected function configureEventManager(EventManager $eventManager)
	{
		// Adds prefix for tables
		$eventManager->addEventSubscriber(new TableNamePrefixer($this->tableNamePrefix, $this->tableNamePrefixNamespace));

		// Updates creation and modification timestamps for appropriate entities
		$eventManager->addEventSubscriber(new TimestampableListener());

		// Maps revision property for appropriate entities
		$eventManager->addEventSubscriber(new Listener\EntityRevisionFieldMapperListener());

		// Nested set entities (pages and files) depends on this listener
		$eventManager->addEventSubscriber(new NestedSetListener());

		// Drops file storage cache group when files are being changed
		$eventManager->addEventSubscriber(new FileGroupCacheDropListener());

		$eventManager->addEventListener(array(Events::loadClassMetadata), new DetachedDiscriminatorHandler());
	}

	protected function configureEntityManager(EntityManager $entityManager)
	{
		$entityManager->getConfiguration()->addCustomHydrationMode(ColumnHydrator::HYDRATOR_ID, new ColumnHydrator($entityManager));

		$databasePlatform = $entityManager->getConnection()->getDatabasePlatform();
		$databasePlatform->markDoctrineTypeCommented(Type::getType(SupraIdType::NAME));
		$databasePlatform->markDoctrineTypeCommented(Type::getType(PathType::NAME));
		$databasePlatform->registerDoctrineTypeMapping('blob', 'text');
		$databasePlatform->registerDoctrineTypeMapping('mediumblob', 'text');
		$databasePlatform->registerDoctrineTypeMapping('enum', 'string');

		// for debugging and su:schema:update command
		$entityManager->_mode = $this->name;
	}

	protected function bindEntityManagerInObjectRepository(EntityManager $entityManager)
	{
		foreach ((array) $this->objectRepositoryBindings as $namespace) {
			ObjectRepository::setEntityManager($namespace, $entityManager);
		}
		ObjectRepository::setEntityManager($this->name, $entityManager);
	}

}
