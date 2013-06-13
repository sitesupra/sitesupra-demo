<?php

function outputInternalServerError()
{
    header('Content-Type: text/html; charset=utf-8');
    $errorPage = file_get_contents('500.html');
    echo $errorPage;
    die();
}

// Bootstrap supra
define('SUPRA_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once SUPRA_PATH . 'lib/Supra/bootstrap.php';

// Start the front cotroller
$frontController = Supra\Controller\FrontController::getInstance();

$frontController->execute();
