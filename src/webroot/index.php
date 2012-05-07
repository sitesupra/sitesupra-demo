<?php

// Bootstrap supra
define('SUPRA_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once SUPRA_PATH . 'lib/Supra/bootstrap.php';

// Start the front cotroller
$frontController = Supra\Controller\FrontController::getInstance();

$frontController->execute();
