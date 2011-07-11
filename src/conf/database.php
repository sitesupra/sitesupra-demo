<?php

use Doctrine\ORM\EntityManager,
		Doctrine\ORM\Configuration,
		Supra\Database\Doctrine,
		Doctrine\Common\Cache\ArrayCache;

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
	SUPRA_LIBRARY_PATH . 'Supra/Controller/Pages/Entity/'
);
$driverImpl = $config->newDefaultAnnotationDriver($entityPaths);
//$driverImpl = new \Doctrine\ORM\Mapping\Driver\YamlDriver(SUPRA_LIBRARY_PATH . 'Supra/yaml/');
$config->setMetadataDriverImpl($driverImpl);

// Proxy configuration
$config->setProxyDir(SUPRA_LIBRARY_PATH . 'Supra/Proxy');
$config->setProxyNamespace('Supra\\Proxy');
$config->setAutoGenerateProxyClasses(true);

// SQL logger
$sqlLogger = new \Supra\Log\Logger\Sql();
$config->setSQLLogger($sqlLogger);

$connectionOptions = array(
	'driver' => 'pdo_mysql',
	'user' => 'root',
	'password' => 'root',
	'dbname' => 'supra7'
);

// TODO: move to some other configuration
$config->addCustomNumericFunction('IF', 'Supra\Database\Doctrine\Functions\IfFunction');

$em = EntityManager::create($connectionOptions, $config);

Doctrine::getInstance()->setDefaultEntityManager($em);
