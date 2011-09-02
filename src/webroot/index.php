<?php

ini_set('zlib.output_compression', 'On');

// Bootstrap supra
require_once dirname(__DIR__) . '/lib/Supra/bootstrap.php';

// Start the front cotroller
$frontController = Supra\Controller\FrontController::getInstance();

// Loading project component configuration
//TODO: should do automatically
require_once SUPRA_COMPONENT_PATH . 'rss/config.php';
require_once SUPRA_COMPONENT_PATH . 'pages/config.php';
require_once SUPRA_COMPONENT_PATH . 'text/config.php';
require_once SUPRA_COMPONENT_PATH . 'distributed-controller/config.php';
require_once SUPRA_WEBROOT_PATH . 'cms/config.php';
require_once SUPRA_COMPONENT_PATH . 'authentication/config.php';

$frontController->execute();
