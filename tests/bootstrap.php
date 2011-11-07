<?php

use Supra\Loader\Strategy\NamespaceLoaderStrategy;
use Supra\Loader\Loader;
use Supra\Tests\ObjectRepository\Mockup\ObjectRepository;

require_once __DIR__ . '/../src/lib/Supra/bootstrap.php';

// Register namespaces for tests
$loader = Loader::getInstance();

define('SUPRA_TESTS_PATH', SUPRA_PATH . '../tests/src/');
define('SUPRA_TESTS_CONF_PATH', SUPRA_TESTS_PATH . 'conf/');
define('SUPRA_TESTS_LIBRARY_PATH', SUPRA_TESTS_PATH . 'lib/');

// Supra test namespace registering
$supraTestsNamespace = new NamespaceLoaderStrategy('Supra\Tests', SUPRA_TESTS_LIBRARY_PATH . 'Supra');
$loader->registerNamespace($supraTestsNamespace);

// Load test connection as well
require_once SUPRA_TESTS_CONF_PATH . 'configuration.php';

ObjectRepository::saveCurrentState();
