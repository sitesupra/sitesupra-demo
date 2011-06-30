<?php

// Supra starting
define('SUPRA_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR);

require_once SUPRA_PATH . 'lib/Supra/bootstrap.php';

// Register namespaces for tests
$loader = \Supra\Loader\Registry::getInstance();

define('SUPRA_TESTS_PATH', SUPRA_PATH . '../tests/src/');
define('SUPRA_TESTS_CONF_PATH', SUPRA_TESTS_PATH . 'conf/');
define('SUPRA_TESTS_LIBRARY_PATH', SUPRA_TESTS_PATH . 'lib/');

// Supra test namespace registering
$supraTestsNamespace = new \Supra\Loader\NamespaceRecord('Supra\Tests', SUPRA_TESTS_LIBRARY_PATH . 'Supra');
$loader->registerNamespace($supraTestsNamespace);

// Doctrine test namespace registering
//$doctrineTestsNamespace = new \Supra\Loader\NamespaceRecord('Doctrine\Tests', SUPRA_TESTS_LIBRARY_PATH . 'Doctrine');
//$loader->registerNamespace($doctrineTestsNamespace);

require_once SUPRA_TESTS_CONF_PATH . 'configuration.php';
