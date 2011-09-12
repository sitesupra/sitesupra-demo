<?php

ini_set('zlib.output_compression', 'On');

// Bootstrap supra
require_once dirname(__DIR__) . '/lib/Supra/bootstrap.php';

// Start the front cotroller
$frontController = Supra\Controller\FrontController::getInstance();

// Loading project component configuration
//TODO: should do automatically
require_once SUPRA_COMPONENT_PATH . 'Rss/config.php';
require_once SUPRA_COMPONENT_PATH . 'Pages/config.php';
require_once SUPRA_COMPONENT_PATH . 'Text/config.php';
require_once SUPRA_COMPONENT_PATH . 'DistributedController/config.php';
require_once SUPRA_COMPONENT_PATH . 'Authentication/config.php';
require_once SUPRA_COMPONENT_PATH . 'Authenticate/config.php';
require_once SUPRA_WEBROOT_PATH . 'cms/config.php';
require_once SUPRA_COMPONENT_PATH . 'Locale/config.php';

$frontController->execute();
