<?php

define('SUPRA_PATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

require_once SUPRA_PATH . 'library/Supra/bootstrap.php';

// Start the front cotroller
$frontController = new \Supra\Controller\Front();

//TODO: should do automatically
require_once SUPRA_PATH . 'components/rss/config.php';

// Test
$cssRouter = new \Supra\Controller\Router\Uri('/rss');

$frontController->route($cssRouter, '\\Project\\Rss\\Controller');

$frontController->execute();

echo 'OK';