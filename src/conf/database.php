<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Supra\Database\Doctrine;
use Doctrine\Common\Cache\ArrayCache;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\Events;
use Supra\Controller\Pages\Listener\PagePathGenerator;
use Doctrine\Common\EventManager;
use Supra\NestedSet\Listener\NestedSetListener;
use Supra\Controller\Pages\Listener\TableDraftPrefixAppender;
use Supra\Database\Doctrine\Listener\TableSuffixPrepender;
use Supra\Controller\Pages\Listener\PublicVersionedTableIdChange;
use Supra\Database\Doctrine\Hydrator\ColumnHydrator;

$config = new Configuration();

// Doctrine cache (array cache for development)
$cache = new ArrayCache();

// Memcache cache configuration sample
//$cache = new \Doctrine\Common\Cache\MemcacheCache();
//$memcache = new \Memcache();
//$memcache->addserver('127.0.0.1');
//$cache->setMemcache($memcache);

//NB! Must have different namespace for draft connection
$cache->setNamespace('public');
$config->setMetadataCacheImpl($cache);
$config->setQueryCacheImpl($cache);

// Metadata driver
$entityPaths = array(
	SUPRA_LIBRARY_PATH . 'Supra/Controller/Pages/Entity/',
	SUPRA_LIBRARY_PATH . 'Supra/FileStorage/Entity/',
	SUPRA_LIBRARY_PATH . 'Supra/User/Entity/',
);
$driverImpl = $config->newDefaultAnnotationDriver($entityPaths);
//$driverImpl = new \Doctrine\ORM\Mapping\Driver\YamlDriver(SUPRA_LIBRARY_PATH . 'Supra/yaml/');
$config->setMetadataDriverImpl($driverImpl);

// Proxy configuration
$config->setProxyDir(SUPRA_LIBRARY_PATH . 'Supra/Proxy');
$config->setProxyNamespace('Supra\\Proxy');
$config->setAutoGenerateProxyClasses(true);

// SQL logger
$sqlLogger = new \Supra\Log\Logger\SqlLogger();
$config->setSQLLogger($sqlLogger);

$connectionOptions = $ini['database'];

// TODO: move to some other configuration
$config->addCustomNumericFunction('IF', 'Supra\Database\Doctrine\Functions\IfFunction');

$commonEventManager = new EventManager();
$commonEventManager->addEventListener(array(Events::onFlush), new PagePathGenerator());
$commonEventManager->addEventListener(array(Events::prePersist, Events::postLoad), new NestedSetListener());
$commonEventManager->addEventListener(array(Events::loadClassMetadata), new TableSuffixPrepender());

$eventManager = clone($commonEventManager);
$eventManager->addEventListener(array(Events::loadClassMetadata), new PublicVersionedTableIdChange());

$em = EntityManager::create($connectionOptions, $config, $eventManager);
$em->getConfiguration()->addCustomHydrationMode(ColumnHydrator::HYDRATOR_ID, new ColumnHydrator($em));
$em->_mode = 'public';

ObjectRepository::setDefaultEntityManager($em);

$config = clone($config);
$cache = clone($cache);
$cache->setNamespace('draft');
$config->setMetadataCacheImpl($cache);
$config->setQueryCacheImpl($cache);

// Draft connection for the CMS
$eventManager = clone($commonEventManager);
$eventManager->addEventListener(array(Events::loadClassMetadata), new TableDraftPrefixAppender());

$em = EntityManager::create($connectionOptions, $config, $eventManager);
$em->getConfiguration()->addCustomHydrationMode(ColumnHydrator::HYDRATOR_ID, new ColumnHydrator($em));
$em->_mode = 'draft';

ObjectRepository::setEntityManager('Supra\Cms', $em);
