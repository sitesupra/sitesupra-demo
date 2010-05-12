<?php

// Base output buffering
ob_start();

// Check the PHP version installed. Supra7 requires 5.3 version as minimum
const SUPRA_REQUIRED_PHP_VERSION = '5.3';
if (version_compare(phpversion(),  SUPRA_REQUIRED_PHP_VERSION, 'lt')) {
	die ('Fatal error: PHP version ' .  SUPRA_REQUIRED_PHP_VERSION . ' or higher is required, version ' . phpversion() . ' found.');
}

// Define the main supra folders
if ( ! defined('SUPRA_PATH')) {
	define('SUPRA_PATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
}
define('SUPRA_LIBRARY_PATH', SUPRA_PATH . 'library' . DIRECTORY_SEPARATOR);
define('SUPRA_WEBROOT_PATH', SUPRA_PATH . 'webroot' . DIRECTORY_SEPARATOR);
define('SUPRA_LOG_PATH', SUPRA_PATH . 'log' . DIRECTORY_SEPARATOR);
define('SUPRA_CONF_PATH', SUPRA_PATH . 'conf' . DIRECTORY_SEPARATOR);

set_include_path(SUPRA_LIBRARY_PATH . PATH_SEPARATOR . get_include_path());

// Require all to the loader required classes
$loaderPath = SUPRA_LIBRARY_PATH
		. 'Supra' . DIRECTORY_SEPARATOR
		. 'Loader' . DIRECTORY_SEPARATOR;

require_once $loaderPath . 'Registry.php';
require_once $loaderPath . 'NamespaceRecord.php';
require_once $loaderPath . 'Exception.php';

// Initiate and set the root namespace directory to the loader
$loader = \Supra\Loader\Registry::getInstance();
$loader->registerRootNamespace(SUPRA_LIBRARY_PATH);
$testsNamespace = new \Supra\Loader\NamespaceRecord('Supra\\Tests', SUPRA_PATH . 'tests/Supra');
$loader->registerNamespace($testsNamespace);
spl_autoload_register(array($loader, 'autoload'));

// Set the initial timezone to the logger
\Supra\Log\Logger::setDefaultTimezone(date_default_timezone_get());

// Ask Supra to handle the PHP generated errors
$phpErrorHandler = new \Supra\Log\Plugin\PhpErrorHandler();
$phpErrorHandler();

// Alias \Log to the \Supra\Log\Logger
class_alias('\Supra\Log\Logger', 'Log');

require_once SUPRA_CONF_PATH . 'configuration.php';