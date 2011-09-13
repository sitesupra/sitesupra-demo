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

$eventManager = new EventManager();
$eventManager->addEventListener(array(Events::onFlush), new PagePathGenerator());
$eventManager->addEventListener(array(Events::prePersist, Events::postLoad), new NestedSetListener());
$eventManager->addEventListener(array(Events::loadClassMetadata), new TableSuffixPrepender());

$em = EntityManager::create($connectionOptions, $config, $eventManager);

ObjectRepository::setDefaultEntityManager($em);

$draftEventManager = clone($eventManager);

// Draft connection for the CMS
$draftEventManager->addEventListener(array(Events::loadClassMetadata), new TableDraftPrefixAppender());

$em = EntityManager::create($connectionOptions, $config, $draftEventManager);

//FIXME: publish doesn't work right yet...
//ObjectRepository::setEntityManager('Supra\Cms', $em);
