<?php

// Bootstrap supra
require_once dirname(__DIR__) . '/lib/Supra/bootstrap.php';

// Start the front cotroller
$frontController = Supra\Controller\FrontController::getInstance();

$frontController->execute();
