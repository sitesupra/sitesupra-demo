<?php

use Doctrine\ORM\EntityManager,
		Doctrine\ORM\Configuration,
		Supra\Database\Doctrine,
		Supra\Controller\Pages;

$config = new Configuration();

// Doctrine cache (array cache for development)
$cache = new \Doctrine\Common\Cache\ArrayCache();
$config->setMetadataCacheImpl($cache);
$config->setQueryCacheImpl($cache);

// Metadata driver
$entityPaths = array(
		SUPRA_TESTS_LIBRARY_PATH . 'Supra/NestedSet/Model',
		SUPRA_LIBRARY_PATH . 'Supra/Controller/Pages/Entity/',
);
$driverImpl = $config->newDefaultAnnotationDriver($entityPaths);
$config->setMetadataDriverImpl($driverImpl);

// Proxy configuration
$config->setProxyDir(SUPRA_TESTS_LIBRARY_PATH . 'Supra/NestedSet/Model/Proxy');
$config->setProxyNamespace('Supra\Tests\Proxy');

// SQL logger
$sqlLogger = new \Supra\Log\Logger\Sql();
$config->setSQLLogger($sqlLogger);

$connectionOptions = array(
	'driver' => 'pdo_pgsql',
	'user' => 'arena',
	'password' => 'arena',
	'dbname' => 'supra7test'
);

$config->addCustomNumericFunction('IF', 'Supra\Database\Doctrine\Functions\IfFunction');

$em = EntityManager::create($connectionOptions, $config);

$connectionName = 'test';

$supraDatabase = Doctrine::getInstance();
$supraDatabase->setEntityManager($connectionName, $em);

Pages\Entity\Abstraction\Entity::setConnectionName($connectionName);