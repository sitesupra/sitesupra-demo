<?php

define('SUPRA_PATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

require_once SUPRA_PATH . 'lib/Supra/bootstrap.php';

// Start the front cotroller
$frontController = new \Supra\Controller\Front();

//TODO: should do automatically
require_once SUPRA_COMPONENT_PATH . 'rss/config.php';
require_once SUPRA_COMPONENT_PATH . 'pages/config.php';

$frontController->execute();

echo 'OK';