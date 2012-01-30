<?php

// Bootstrap supra
require_once dirname(__DIR__) . '/lib/Supra/bootstrap.php';

// Start the front cotroller
$frontController = Supra\Controller\FrontController::getInstance();

// Loading project component configuration
//TODO: should do automatically
// NOTE only RSS left here, others moved to configuration.php (YAML parser)
require_once SUPRA_COMPONENT_PATH . 'Rss/config.php';

$frontController->execute();
