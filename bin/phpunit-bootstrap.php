<?php

// Supra starting
define('SUPRA_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR);

require_once SUPRA_PATH . 'lib/Supra/bootstrap.php';

$loader = \Supra\Loader\Registry::getInstance();

// Supra test namespace registering
$supraTestsNamespace = new \Supra\Loader\NamespaceRecord('Supra\\Tests', SUPRA_PATH . '../tests/src/lib/Supra');
$loader->registerNamespace($supraTestsNamespace);

// Doctrine test namespace registering
$doctrineTestsNamespace = new \Supra\Loader\NamespaceRecord('Doctrine\\Tests', SUPRA_PATH . '../tests/src/lib/Doctrine');
$loader->registerNamespace($doctrineTestsNamespace);
