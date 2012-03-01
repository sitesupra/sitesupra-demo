<?php

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

Type::addType(SupraIdType::NAME, 'Supra\Database\Doctrine\Type\SupraIdType');
Type::addType(PathType::NAME, 'Supra\Database\Doctrine\Type\PathType');
Type::overrideType(Type::DATETIME, 'Supra\Database\Doctrine\Type\UtcDateTimeType');

// TODO: Remove later
Type::addType('block', 'Supra\Database\Doctrine\Type\UnknownType');
Type::addType('sha1', 'Supra\Database\Doctrine\Type\UnknownType');
Type::addType('template', 'Supra\Database\Doctrine\Type\UnknownType');
Type::addType('supraId', 'Supra\Database\Doctrine\Type\UnknownType');

// TODO: use configuration classes maybe?
$managerNames = array(
	PageController::SCHEMA_PUBLIC => '',
	PageController::SCHEMA_DRAFT => 'Supra\Cms',
	PageController::SCHEMA_AUDIT => 'Supra\Cms\Abstraction\Audit',
);

$ini = ObjectRepository::getIniConfigurationLoader('');
$connectionOptions = $ini->getSection('database');

// TODO: Let's see if it is still required with MySQL PDO charset updates in PHP 5.3.6
$connectionOptions['driverOptions'] = array(
		PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
);

foreach ($managerNames as $managerName => $namespace) {
	$config = new Configuration();

	$cacheInstance = ObjectRepository::getCacheAdapter('');
	$cache = new CacheNamespaceWrapper($cacheInstance, $managerName);
	$config->setQueryCacheImpl($cache);
	$config->setResultCacheImpl($cache);
	
	// This will create proxy objects when entity metadata is saved in the cache
	$metadataCache = new Doctrine\Cache\ProxyFactoryMetadataCache($cache);
	$config->setMetadataCacheImpl($metadataCache);

	// Metadata driver
	$entityPaths = array(
			SUPRA_LIBRARY_PATH . 'Supra/Controller/Pages/Entity/',
			SUPRA_LIBRARY_PATH . 'Supra/FileStorage/Entity/',
			// This is required because of Proxy objects!
			SUPRA_LIBRARY_PATH . 'Supra/User/Entity/',
			SUPRA_LIBRARY_PATH . 'Supra/Console/Cron/Entity/',
			SUPRA_LIBRARY_PATH . 'Supra/Search/Entity',
			SUPRA_LIBRARY_PATH . 'Supra/BannerMachine/Entity',
			SUPRA_LIBRARY_PATH . 'Supra/Payment/Entity',
			SUPRA_LIBRARY_PATH . 'Supra/Mailer/MassMail/Entity',
	);
	$driverImpl = $config->newDefaultAnnotationDriver($entityPaths);
	//$driverImpl = new \Doctrine\ORM\Mapping\Driver\YamlDriver(SUPRA_LIBRARY_PATH . 'Supra/yaml/');
	$config->setMetadataDriverImpl($driverImpl);

	// Proxy configuration
	$config->setProxyDir(SUPRA_LIBRARY_PATH . 'Supra/Proxy/');
	$config->setProxyNamespace('Supra\\Proxy');

	// TODO: should disable generation on production and pregenerate on build
	$config->setAutoGenerateProxyClasses(false);

	// SQL logger
	$sqlLogger = new \Supra\Log\Logger\EventsSqlLogger();
	$config->setSQLLogger($sqlLogger);

	// TODO: move to some other configuration
	$config->addCustomNumericFunction('IF', 'Supra\Database\Doctrine\Functions\IfFunction');

	$eventManager = new EventManager();
	$eventManager->addEventSubscriber(new TableNamePrefixer('su_'));
	$eventManager->addEventSubscriber(new TimestampableListener());

	$eventManager->addEventSubscriber(new Listener\VersionedAnnotationListener());

	$eventManager->addEventListener(array(Events::loadClassMetadata), new Listener\EntityRevisionListener());

	$eventManager->addEventSubscriber(new NestedSetListener());
	$eventManager->addEventSubscriber(new FileGroupCacheDropListener());
	
	switch ($managerName) {
		case PageController::SCHEMA_PUBLIC:
			$eventManager->addEventSubscriber(new Listener\PagePathGenerator());
			$eventManager->addEventSubscriber(new Listener\PageGroupCacheDropListener());
			break;

		case PageController::SCHEMA_DRAFT:
			$eventManager->addEventSubscriber(new Listener\PagePathGenerator());
			$eventManager->addEventSubscriber(new Listener\ImageSizeCreatorListener());
			$eventManager->addEventSubscriber(new Listener\TableDraftSuffixAppender());
			
			// NB! ORDER DOES MATTER!
			// Revision id must be filled before entity goes to audit listener
			// Manage entity revision values
			$eventManager->addEventSubscriber(new Listener\EntityRevisionListener());
			// Audit entity changes in Draft schema
			$eventManager->addEventSubscriber(new Listener\EntityAuditListener());
			break;
		case PageController::SCHEMA_AUDIT:
			$eventManager->addEventSubscriber(new Listener\AuditManagerListener());
			$eventManager->addEventSubscriber(new Listener\AuditCreateSchemaListener());
			break;
	}

	$em = EntityManager::create($connectionOptions, $config, $eventManager);
	$em->getConfiguration()->addCustomHydrationMode(ColumnHydrator::HYDRATOR_ID, new ColumnHydrator($em));
	$em->getConnection()->getDatabasePlatform()->markDoctrineTypeCommented(Type::getType(SupraIdType::NAME));
	$em->getConnection()->getDatabasePlatform()->markDoctrineTypeCommented(Type::getType(PathType::NAME));
	$em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('blob', 'text');
	$em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('mediumblob', 'text');
	$em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');	
	
	// for debugging only
	$em->_mode = $managerName;

	ObjectRepository::setEntityManager($namespace, $em);
	ObjectRepository::setEntityManager($managerName, $em);
}
