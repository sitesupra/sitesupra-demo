<?php

namespace Supra\Cms;

use Supra\Router\UriRouter;
use Supra\Loader\Registry;

// Load manually
require_once __DIR__ . DIRECTORY_SEPARATOR . 'CmsNamespaceRecord.php';

// Register namespace
$namespace = new CmsNamespaceRecord('Supra\Cms', __DIR__);
Registry::getInstance()->registerNamespace($namespace);

//// TODO: temporary solution for namespace autoloading for folders with "-"
//$namespace = new Supra\Loader\NamespaceRecord('Supra\Cms\ContentManager', __DIR__ . '/content-manager');
//Supra\Loader\Registry::getInstance()->registerNamespace($namespace);
//$namespace = new Supra\Loader\NamespaceRecord('Supra\Cms\InternalUserManager', __DIR__ . '/internal-user-manager');
//Supra\Loader\Registry::getInstance()->registerNamespace($namespace);
//$namespace = new Supra\Loader\NamespaceRecord('Supra\Cms\MediaLibrary', __DIR__ . '/media-library');
//Supra\Loader\Registry::getInstance()->registerNamespace($namespace);

$router = new UriRouter('/cms');
$frontController->route($router, 'Supra\Cms\CmsController');
