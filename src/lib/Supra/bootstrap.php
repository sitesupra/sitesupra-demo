<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

// Base output buffering
ob_start();

// Check the PHP version installed. Supra7 requires 5.3.3 version as minimum
const SUPRA_REQUIRED_PHP_VERSION = '5.3.3';
if (version_compare(phpversion(),  SUPRA_REQUIRED_PHP_VERSION, 'lt')) {
	die('Fatal error: PHP version ' .  SUPRA_REQUIRED_PHP_VERSION . ' or higher is required, version ' . phpversion() . ' found.' . PHP_EOL);
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
define('SUPRA_COMPONENT_PATH', SUPRA_PATH . '../local/Project' . DIRECTORY_SEPARATOR);
// TODO: not used for now. Maybe should removed completely.
//define('SUPRA_DATA_PATH', SUPRA_PATH . 'data' . DIRECTORY_SEPARATOR);
define('SUPRA_TEMPLATE_PATH', SUPRA_PATH . 'template' . DIRECTORY_SEPARATOR);
define('SUPRA_TMP_PATH', SUPRA_PATH . 'tmp' . DIRECTORY_SEPARATOR);
define('SUPRA_ERROR_PAGE_PATH', SUPRA_WEBROOT_PATH);

function outputInternalServerError()
{
    header('Content-Type: text/html; charset=utf-8');
    $webrootDir = SUPRA_ERROR_PAGE_PATH;
    $errorFile = $webrootDir.'500.html';
    if (file_exists($errorFile)) {
        $errorPage = file_get_contents($errorFile);
    } else {
        $errorPage = SUPRA_ERROR_MESSAGE;
    }
    echo $errorPage;
    die();
}

// This is not required for currently used libraries
//set_include_path(SUPRA_LIBRARY_PATH . PATH_SEPARATOR . get_include_path());

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

// Can control new file/folder default permissions there
if (file_exists(SUPRA_CONF_PATH . 'chmod.php')) {
	require_once SUPRA_CONF_PATH . 'chmod.php';
}

// Default permission modes for new files and folders
if ( ! defined('SITESUPRA_FOLDER_PERMISSION_MODE')) {
	define('SITESUPRA_FOLDER_PERMISSION_MODE', 0755);
}

if ( ! defined('SITESUPRA_FILE_PERMISSION_MODE')) {
	define('SITESUPRA_FILE_PERMISSION_MODE', 0644);
}

try {

	// TODO: validate the value
	$profile = getenv('SUPRA_PROFILE');

	if ( ! empty($profile)) {
		require_once SUPRA_CONF_PATH . 'configuration.' . $profile . '.php';
	} else {
		require_once SUPRA_CONF_PATH . 'configuration.php';
	}
} catch (\Exception $e) {
        if (true) { //@todo: debug handling here
            throw $e;
        }
	\Log::fatal("Application configuration load failed: " . (string) $e);
	outputInternalServerError();
}

