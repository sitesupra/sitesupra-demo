<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Supra\Database\Doctrine;
use Doctrine\Common\Cache\ArrayCache;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\Events;
use Doctrine\Common\EventManager;
use Supra\NestedSet\Listener\NestedSetListener;
use Supra\Database\Doctrine\Listener\TableNameGenerator;
use Supra\Controller\Pages\Listener;
use Supra\Database\Doctrine\Hydrator\ColumnHydrator;

$config = new Configuration();

// Doctrine cache (array cache for development)
$cache = new ArrayCache();
//$cache = new \Doctrine\Common\Cache\MemcacheCache();
//$memcache = new \Memcache();
//$memcache->addserver('127.0.0.1');
//$cache->setMemcache($memcache);
$config->setMetadataCacheImpl($cache);
$config->setQueryCacheImpl($cache);

// Metadata driver
$entityPaths = array(
	SUPRA_LIBRARY_PATH . 'Supra/Controller/Pages/Entity/',
	SUPRA_LIBRARY_PATH . 'Supra/FileStorage/Entity/',
	SUPRA_LIBRARY_PATH . 'Supra/User/Entity/',
	SUPRA_TESTS_LIBRARY_PATH . 'Supra/NestedSet/Model',
);
$driverImpl = $config->newDefaultAnnotationDriver($entityPaths);
//$driverImpl = new \Doctrine\ORM\Mapping\Driver\YamlDriver(SUPRA_LIBRARY_PATH . 'Supra/yaml/');
$config->setMetadataDriverImpl($driverImpl);

// Proxy configuration
$config->setProxyDir(SUPRA_TESTS_LIBRARY_PATH . 'Supra/Proxy');
$config->setProxyNamespace('Supra\\Proxy');
$config->setAutoGenerateProxyClasses(true);

// SQL logger
$sqlLogger = new \Supra\Log\Logger\SqlLogger();
$config->setSQLLogger($sqlLogger);

$connectionOptions = array(
	'driver' => 'pdo_mysql',
	'user' => 'root',
	'password' => 'root',
	'dbname' => 'supra7test'
);

// TODO: move to some other configuration
$config->addCustomNumericFunction('IF', 'Supra\Database\Doctrine\Functions\IfFunction');

$eventManager = new EventManager();
$eventManager->addEventListener(array(Events::onFlush), new Listener\PagePathGenerator());
$eventManager->addEventListener(array(Events::prePersist, Events::postLoad), new NestedSetListener());
$eventManager->addEventListener(array(Events::loadClassMetadata), new TableNameGenerator());
$eventManager->addEventListener(array(Events::onFlush), new Listener\ImageSizeCreatorListener());

$em = EntityManager::create($connectionOptions, $config, $eventManager);
$em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('db_sha1', 'sha1');
$em->getConfiguration()->addCustomHydrationMode(ColumnHydrator::HYDRATOR_ID, new ColumnHydrator($em));

ObjectRepository::setEntityManager('Supra\Tests', $em);
