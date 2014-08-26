<?php

use Supra\Tests\ObjectRepository\Mockup\ObjectRepository;

require_once __DIR__ . '/../src/lib/Supra/bootstrap.php';

define('SUPRA_TESTS_PATH', SUPRA_PATH . '../tests/src/');
define('SUPRA_TESTS_CONF_PATH', SUPRA_TESTS_PATH . 'conf/');
define('SUPRA_TESTS_LIBRARY_PATH', SUPRA_TESTS_PATH . 'lib/');

ComposerAutoloaderInitSupra::getLoader()->addPsr4('Supra\Tests\\', SUPRA_TESTS_LIBRARY_PATH . '/Supra/');

// Load test connection as well
require_once SUPRA_TESTS_CONF_PATH . 'configuration.php';

ObjectRepository::saveCurrentState();
