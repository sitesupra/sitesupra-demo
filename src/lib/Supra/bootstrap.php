<?php

// Base output buffering
ob_start();

// Check the PHP version installed. Supra7 requires 5.3 version as minimum
const SUPRA_REQUIRED_PHP_VERSION = '5.3';
if (version_compare(phpversion(),  SUPRA_REQUIRED_PHP_VERSION, 'lt')) {
	die('Fatal error: PHP version ' .  SUPRA_REQUIRED_PHP_VERSION . ' or higher is required, version ' . phpversion() . ' found.');
}

// Define the main supra folders
if ( ! defined('SUPRA_PATH')) {
	define('SUPRA_PATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
}
define('SUPRA_LIBRARY_PATH', SUPRA_PATH . 'lib' . DIRECTORY_SEPARATOR);
define('SUPRA_WEBROOT_PATH', SUPRA_PATH . 'webroot' . DIRECTORY_SEPARATOR);
define('SUPRA_LOG_PATH', SUPRA_PATH . 'log' . DIRECTORY_SEPARATOR);
define('SUPRA_CONF_PATH', SUPRA_PATH . 'conf' . DIRECTORY_SEPARATOR);
define('SUPRA_COMPONENT_PATH', SUPRA_WEBROOT_PATH . 'components' . DIRECTORY_SEPARATOR);
define('SUPRA_DATA_PATH', SUPRA_PATH . 'data' . DIRECTORY_SEPARATOR);
define('SUPRA_TEMPLATE_PATH', SUPRA_PATH . 'template' . DIRECTORY_SEPARATOR);
define('SUPRA_TMP_PATH', SUPRA_PATH . 'tmp' . DIRECTORY_SEPARATOR);

// This is not required for currently used libraries
//set_include_path(SUPRA_LIBRARY_PATH . PATH_SEPARATOR . get_include_path());

// Require all to the loader required classes
$loaderPath = SUPRA_LIBRARY_PATH
		. 'Supra' . DIRECTORY_SEPARATOR
		. 'Loader' . DIRECTORY_SEPARATOR;

require_once $loaderPath . 'Registry.php';
require_once $loaderPath . 'NamespaceRecord.php';

// Initiate and set the root namespace directory to the loader
$loader = Supra\Loader\Registry::getInstance();

// Set Supra namespace
$supraNamespace = new Supra\Loader\NamespaceRecord('Supra', SUPRA_LIBRARY_PATH . 'Supra');
$loader->registerNamespace($supraNamespace);

// Set Doctrine namespace
$doctrineNamespace = new Supra\Loader\NamespaceRecord('Doctrine', SUPRA_LIBRARY_PATH . 'Doctrine');
$loader->registerNamespace($doctrineNamespace);

// Set Symfony namespace
$symfonyNamespace = new Supra\Loader\NamespaceRecord('Symfony', SUPRA_LIBRARY_PATH . 'Symfony');
$loader->registerNamespace($symfonyNamespace);

// Twig autoloader, TODO: should write such supra7 autoloader
require_once SUPRA_LIBRARY_PATH . 'Twig' . DIRECTORY_SEPARATOR . 'Autoloader.php';
Twig_Autoloader::register();

$loader->registerSystemAutoload();

// Set the initial timezone to the logger
Supra\Log\LogEvent::setDefaultTimezone(date_default_timezone_get());

// Ask Supra to handle the PHP generated errors
$phpErrorHandler = new Supra\Log\Plugin\PhpErrorHandler();
$phpErrorHandler();

// Alias Log to the Supra\Log\Log
class_alias('Supra\Log\Log', 'Log');

// Set mb enciding to UTF-8
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');

require_once SUPRA_CONF_PATH . 'configuration.php';
