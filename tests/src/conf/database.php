<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Supra\Database\Doctrine;
use Doctrine\Common\Cache\ArrayCache;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\Events;
use Doctrine\Common\EventManager;
use Supra\NestedSet\Listener\NestedSetListener;
use Supra\Tests\Database\TableNameGenerator;
use Supra\Controller\Pages\Listener;
use Supra\Database\Doctrine\Hydrator\ColumnHydrator;
use Doctrine\DBAL\Types\Type;
use Supra\Database\Doctrine\Type\Sha1HashType;
use Supra\Database\Doctrine\Type\PathType;
use Supra\Database\Doctrine\Listener\TimestampableListener;

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
		SUPRA_LIBRARY_PATH . 'Supra/Search/Entity',
		SUPRA_TESTS_LIBRARY_PATH . 'Supra/NestedSet/Model',
		SUPRA_TESTS_LIBRARY_PATH . 'Supra/Search/Entity',
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

$connectionOptions = $ini['database'];

// TODO: Let's see if it is still required with MySQL PDO charset updates in PHP 5.3.6
$connectionOptions['driverOptions'] = array(
		PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
);

// TODO: move to some other configuration
$config->addCustomNumericFunction('IF', 'Supra\Database\Doctrine\Functions\IfFunction');

$eventManager = new EventManager();
$eventManager->addEventListener(array(Events::onFlush), new Listener\PagePathGenerator());
$eventManager->addEventListener(array(Events::prePersist, Events::postLoad, Events::preRemove), new NestedSetListener());
$eventManager->addEventListener(array(Events::loadClassMetadata), new TableNameGenerator());
$eventManager->addEventListener(array(Events::onFlush), new Listener\ImageSizeCreatorListener());
$eventManager->addEventListener(array(Events::onFlush, Events::prePersist), new TimestampableListener());
$eventManager->addEventListener(array(Events::loadClassMetadata), new Supra\Tests\Search\DiscriminatorAppender());


$em = EntityManager::create($connectionOptions, $config, $eventManager);
$em->getConfiguration()->addCustomHydrationMode(ColumnHydrator::HYDRATOR_ID, new ColumnHydrator($em));
$em->getConnection()->getDatabasePlatform()->markDoctrineTypeCommented(Type::getType(Sha1HashType::NAME));
$em->getConnection()->getDatabasePlatform()->markDoctrineTypeCommented(Type::getType(PathType::NAME));
$em->_mode = 'test';

ObjectRepository::setEntityManager('Supra\Tests', $em);
