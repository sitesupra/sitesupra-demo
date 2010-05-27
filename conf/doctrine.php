<?php

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\Configuration,
	Supra\Database\Doctrine;

$config = new Configuration;

// Doctrine cache
$memcache = new \Memcache();
$memcache->pconnect('127.0.0.1');
$cache = new \Doctrine\Common\Cache\MemcacheCache();
$cache->setMemcache($memcache);
$config->setMetadataCacheImpl($cache);
$config->setQueryCacheImpl($cache);

// Metadata driver
$driverImpl = $config->newDefaultAnnotationDriver(SUPRA_LIBRARY_PATH . 'Supra/');
$config->setMetadataDriverImpl($driverImpl);

// Proxy configuration
$config->setProxyDir(SUPRA_LIBRARY_PATH . 'Supra/Proxy');
$config->setProxyNamespace('Supra\\Proxy');

// SQL logger
$sqlLogger = new \Supra\Log\Logger\Sql();
$config->setSQLLogger($sqlLogger);

$config->setAutoGenerateProxyClasses(false);

$connectionOptions = array(
    'driver' => 'pdo_sqlite',
    'path' => SUPRA_DATA_PATH . 'database.sqlite'
);

$em = EntityManager::create($connectionOptions, $config);

Doctrine::getInstance()->setDefaultEntityManager($em);

/*
$tool = new \Doctrine\ORM\Tools\SchemaTool($em);
$classes = array(
  $em->getClassMetadata('Supra\\Controller\\Pages\\Page'),
);

$tool->createSchema($classes);
*/

/*
$page = new Supra\Controller\Pages\Page();
$page->setId(1);
$em->persist($page);
$em->flush();
*/

/*
$data = $em->find('Supra\\Controller\\Pages\\Page', 1);

\Log::error($data);
 */

/*
$data = $em->getReference('Supra\\Controller\\Pages\\Page', 1);
\Log::Error($data);
*/

