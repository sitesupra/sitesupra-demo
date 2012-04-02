<?php

use Supra\Loader\Loader;
use Supra\Loader\Strategy\NamespaceLoaderStrategy;
use Supra\Loader\Strategy\SupraProxyLoadStrategy;

// Base output buffering
ob_start();

// Check the PHP version installed. Supra7 requires 5.3.3 version as minimum
const SUPRA_REQUIRED_PHP_VERSION = '5.3.3';
if (version_compare(phpversion(),  SUPRA_REQUIRED_PHP_VERSION, 'lt')) {
	die('Fatal error: PHP version ' .  SUPRA_REQUIRED_PHP_VERSION . ' or higher is required, version ' . phpversion() . ' found.');
}

define('SUPRA_ERROR_MESSAGE', '500 INTERNAL SERVER ERROR');

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
define('SUPRA_THEMES_PATH', SUPRA_TEMPLATE_PATH . 'themes' . DIRECTORY_SEPARATOR);
define('SUPRA_THEMES_CONF_PATH', SUPRA_CONF_PATH . 'themes' . DIRECTORY_SEPARATOR);
define('SUPRA_TMP_PATH', SUPRA_PATH . 'tmp' . DIRECTORY_SEPARATOR);

// This is not required for currently used libraries
//set_include_path(SUPRA_LIBRARY_PATH . PATH_SEPARATOR . get_include_path());

// Require all to the loader required classes
$loaderPath = SUPRA_LIBRARY_PATH
		. 'Supra' . DIRECTORY_SEPARATOR
		. 'Loader' . DIRECTORY_SEPARATOR;

require_once $loaderPath . 'Loader.php';
require_once $loaderPath . 'Strategy/LoaderStrategyInterface.php';
require_once $loaderPath . 'Strategy/NamespaceLoaderStrategy.php';
require_once $loaderPath . 'Strategy/SupraProxyLoadStrategy.php';

// Initiate and set the root namespace directory to the loader
$loader = Loader::getInstance();

// Important to register right ahead
$loader->registerSystemAutoload();

// Set Supra namespace
$supraNamespace = new NamespaceLoaderStrategy('Supra', SUPRA_LIBRARY_PATH . 'Supra');
$loader->registerNamespace($supraNamespace);

$supraNamespace = new SupraProxyLoadStrategy('Supra\Proxy', SUPRA_LIBRARY_PATH . 'Supra/Proxy');
$loader->registerNamespace($supraNamespace);

// Set Doctrine namespace
$doctrineNamespace = new NamespaceLoaderStrategy('Doctrine', SUPRA_LIBRARY_PATH . 'Doctrine');
$loader->registerNamespace($doctrineNamespace);

// Set Symfony namespace
$symfonyNamespace = new NamespaceLoaderStrategy('Symfony', SUPRA_LIBRARY_PATH . 'Symfony');
$loader->registerNamespace($symfonyNamespace);

// Twig autoloader
$twigLoader = new \Supra\Loader\Strategy\PearLoaderStrategy('Twig', SUPRA_LIBRARY_PATH . 'Twig', false);
$loader->registerNamespace($twigLoader);

// Swift autoloader and initializer
$swiftLoader = new \Supra\Loader\Strategy\PearLoaderStrategy('Swift', SUPRA_LIBRARY_PATH . 'Swift/classes/', true);
$loader->registerNamespace($swiftLoader);
require_once SUPRA_LIBRARY_PATH . 'Swift/swift_init.php';

// Solarium autoloader
$solariumLoader = new \Supra\Loader\Strategy\PearLoaderStrategy('Solarium', SUPRA_LIBRARY_PATH . 'Solarium', false);
$loader->registerNamespace($solariumLoader);

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

try {
	require_once SUPRA_CONF_PATH . 'configuration.php';
} catch (\Exception $e) {
	\Log::fatal("Application configuration load failed: " . (string) $e);
	header('Content-Type: text/plain');
	die(SUPRA_ERROR_MESSAGE);
}
