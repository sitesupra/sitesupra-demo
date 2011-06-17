<?php

define('SUPRA_PATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

require_once SUPRA_PATH . 'lib/Supra/bootstrap.php';

// Start the front cotroller
$frontController = new Supra\Controller\FrontController();

//TODO: should do automatically
require_once SUPRA_COMPONENT_PATH . 'rss/config.php';
//require_once SUPRA_COMPONENT_PATH . 'pages/config.php';

require_once SUPRA_COMPONENT_PATH . 'pages/Configuration.php';
$configuration = new \Project\Pages\Configuration();
//TODO: should be called by MAGIC METHOD RUNNER and provide required attributes
$configuration->configure($frontController, \Supra\Loader\Registry::getInstance());

require_once SUPRA_COMPONENT_PATH . 'text/config.php';

require_once SUPRA_COMPONENT_PATH . 'distributed-controller/config.php';

require_once SUPRA_WEBROOT_PATH . 'cms/content-manager-2/config.php';

$frontController->execute();
