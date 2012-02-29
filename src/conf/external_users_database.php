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
use Supra\Configuration\Exception\ConfigurationMissing;

$ini = ObjectRepository::getIniConfigurationLoader('');
try {
	$connectionOptions = $ini->getSection('external_user_database');
	if ( ! $connectionOptions['active']) {
		return;
	}
} catch (ConfigurationMissing $e) {
	return;
}

// TODO: Let's see if it is still required with MySQL PDO charset updates in PHP 5.3.6
$connectionOptions['driverOptions'] = array(
	PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
);

$managerName = '#ExternalUsers';
$namespace = 'Supra\User';

$config = new Configuration();

$cacheInstance = ObjectRepository::getCacheAdapter('');
$cache = new CacheNamespaceWrapper($cacheInstance);
$cache->setNamespace($managerName);
$config->setMetadataCacheImpl($cache);
$config->setQueryCacheImpl($cache);
$config->setResultCacheImpl($cache);

// Metadata driver
$entityPaths = array(
	SUPRA_LIBRARY_PATH . 'Supra/User/Entity/',
);
$driverImpl = $config->newDefaultAnnotationDriver($entityPaths);
//$driverImpl = new \Doctrine\ORM\Mapping\Driver\YamlDriver(SUPRA_LIBRARY_PATH . 'Supra/yaml/');
$config->setMetadataDriverImpl($driverImpl);

// Proxy configuration
$config->setProxyDir(SUPRA_LIBRARY_PATH . 'Supra/Proxy/');
$config->setProxyNamespace('Supra\\Proxy');

// TODO: should disable generation on production and pregenerate on build
$config->setAutoGenerateProxyClasses(true);

// SQL logger
$sqlLogger = new \Supra\Log\Logger\EventsSqlLogger();
$config->setSQLLogger($sqlLogger);

// TODO: move to some other configuration
$config->addCustomNumericFunction('IF', 'Supra\Database\Doctrine\Functions\IfFunction');

$eventManager = new EventManager();
$eventManager->addEventSubscriber(new TableNamePrefixer('su_'));
$eventManager->addEventSubscriber(new TimestampableListener());

//$eventManager->addEventSubscriber(new Listener\VersionedAnnotationListener());

//$eventManager->addEventListener(array(Events::loadClassMetadata), new Listener\EntityRevisionListener());

$eventManager->addEventSubscriber(new NestedSetListener());

try {
	$em = EntityManager::create($connectionOptions, $config, $eventManager);
} catch (Exception $e) {
	$logger = ObjectRepository::getLogger('');
	$logger->warn('Failed to create connection to external user database. ', $e);
	
	return;
}

$em->getConfiguration()->addCustomHydrationMode(ColumnHydrator::HYDRATOR_ID, new ColumnHydrator($em));
$em->getConnection()->getDatabasePlatform()->markDoctrineTypeCommented(Type::getType(SupraIdType::NAME));
$em->getConnection()->getDatabasePlatform()->markDoctrineTypeCommented(Type::getType(PathType::NAME));
$em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('blob', 'text');
$em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('mediumblob', 'text');
$em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

$em->_mode = $managerName;

ObjectRepository::setEntityManager($namespace, $em);
ObjectRepository::setEntityManager('Supra\Authorization', $em);
ObjectRepository::setEntityManager($managerName, $em);
